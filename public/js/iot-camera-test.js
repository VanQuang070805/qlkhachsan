(() => {
    const root = document.getElementById('face-id-app');
    if (!root) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const video = document.getElementById('laptop-camera');
    const cameraStatus = document.getElementById('camera-status');
    const syncStatus = document.getElementById('sync-status');
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const registerButton = document.getElementById('register-face');
    const testButton = document.getElementById('test-face');
    const cancelButton = document.getElementById('cancel-enrollment');
    const bookingSelect = document.getElementById('booking-id');
    const consent = document.getElementById('face-consent');
    const progress = document.getElementById('sample-progress');
    const completionMessageKey = 'face-id-enrollment-complete';
    let stream = null;
    let sessionId = null;
    let enrolling = false;
    let testing = false;
    const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
    const endpoint = (template, id) => template.replace('__SESSION__', encodeURIComponent(id));

    const savedCompletionMessage = sessionStorage.getItem(completionMessageKey);
    if (savedCompletionMessage) {
        sessionStorage.removeItem(completionMessageKey);
        cameraStatus.className = 'status success';
        cameraStatus.textContent = savedCompletionMessage;
    }

    function enrollmentProgress(data, fallbackTarget = 15) {
        const samples = Number(data?.samples ?? 0);
        const target = Number(data?.target ?? fallbackTarget);
        if (!Number.isFinite(samples) || samples < 0 || !Number.isFinite(target) || target <= 0) {
            throw new Error('Dịch vụ Face ID trả về tiến độ không hợp lệ.');
        }
        return { samples: Math.min(samples, target), target };
    }

    async function jsonRequest(url, options = {}) {
        const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', ...(options.headers || {}) } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Yêu cầu không thành công.');
        return data;
    }

    async function startCamera() {
        if (stream) return;
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) throw new Error('Hãy mở trang bằng localhost hoặc HTTPS để dùng camera.');
        stream = await navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 960 }, height: { ideal: 540 }, facingMode: 'user' }, audio: false });
        video.srcObject = stream;
        video.hidden = false;
        await video.play();
        startButton.disabled = true;
        stopButton.disabled = false;
        cameraStatus.textContent = 'Camera đã sẵn sàng.';
    }

    function stopCamera() {
        if (enrolling || testing) return;
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        startButton.disabled = false;
        stopButton.disabled = true;
        cameraStatus.textContent = 'Camera đã tắt.';
    }

    function captureFrame() {
        return new Promise((resolve, reject) => {
            if (!video.videoWidth) return reject(new Error('Camera chưa có hình ảnh.'));
            const canvas = document.createElement('canvas');
            const scale = Math.min(1, 960 / video.videoWidth);
            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Không chụp được khung hình.')), 'image/jpeg', 0.86);
        });
    }

    const reasonText = { no_face: 'Không thấy khuôn mặt. Nhìn thẳng vào camera.', multiple_faces: 'Chỉ để một người trong khung hình.', face_too_small: 'Đưa khuôn mặt lại gần camera hơn.', too_soon: 'Giữ yên trong giây lát.', ambiguous_face: 'Khuôn mặt đang trùng với nhiều hồ sơ đã lưu. Cần đăng ký lại đúng khách.' };

    async function registerFace() {
        if (!bookingSelect.value) return setCameraError('Hãy chọn khách đang check-in.');
        if (!consent.checked) return setCameraError('Cần xác nhận sự đồng ý của khách.');
        try {
            await startCamera();
            enrolling = true;
            registerButton.disabled = true;
            cancelButton.disabled = false;
            stopButton.disabled = true;
            progress.value = 0;
            cameraStatus.className = 'status';
            const created = await jsonRequest(root.dataset.createSession, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ booking_id: Number(bookingSelect.value), consent: true }) });
            if (typeof created.session_id !== 'string' || !created.session_id) {
                throw new Error('Dịch vụ Face ID không tạo được phiên đăng ký hợp lệ.');
            }
            const initialProgress = enrollmentProgress(created);
            sessionId = created.session_id;
            progress.max = initialProgress.target;
            while (enrolling && sessionId) {
                const form = new FormData();
                form.append('frame', await captureFrame(), 'frame.jpg');
                const result = await jsonRequest(endpoint(root.dataset.sampleUrl, sessionId), { method: 'POST', body: form });
                const currentProgress = enrollmentProgress(result, progress.max);
                progress.max = currentProgress.target;
                progress.value = currentProgress.samples;
                cameraStatus.textContent = result.accepted ? `Samples: ${currentProgress.samples}/${currentProgress.target}` : (reasonText[result.reason] || `Samples: ${currentProgress.samples}/${currentProgress.target}`);
                if (result.complete) {
                    enrolling = false;
                    sessionId = null;
                    cameraStatus.className = 'status success';
                    cameraStatus.textContent = 'Đăng ký Face ID thành công. Đang chờ đồng bộ tới Pi.';
                    cancelButton.disabled = true;
                    stopButton.disabled = false;
                    await refreshHealth();
                    sessionStorage.setItem(completionMessageKey, `Đăng ký Face ID thành công và đã lưu ${currentProgress.samples} mẫu khuôn mặt.`);
                    setTimeout(() => window.location.reload(), 1200);
                    return;
                }
                await sleep(380);
            }
        } catch (error) {
            setCameraError(error.message);
            enrolling = false;
            registerButton.disabled = false;
            cancelButton.disabled = true;
            stopButton.disabled = !stream;
        }
    }

    function setCameraError(message) { cameraStatus.className = 'status error'; cameraStatus.textContent = message; }

    async function testFace() {
        if (enrolling || testing) return;
        let bestScore = null;
        let threshold = null;
        try {
            await startCamera();
            testing = true;
            testButton.disabled = true;
            registerButton.disabled = true;
            stopButton.disabled = true;
            cameraStatus.className = 'status';
            cameraStatus.textContent = 'Đang test Face ID... Nhìn thẳng vào camera.';

            const maximumAttempts = 12;
            for (let attempt = 1; attempt <= maximumAttempts; attempt++) {
                const form = new FormData();
                form.append('frame', await captureFrame(), 'frame.jpg');
                const result = await jsonRequest(root.dataset.recognizeUrl, { method: 'POST', body: form });
                if (result.score !== null && result.score !== undefined && Number.isFinite(Number(result.score)) && (bestScore === null || Number(result.score) > bestScore)) {
                    bestScore = Number(result.score);
                }
                if (result.threshold !== null && result.threshold !== undefined && Number.isFinite(Number(result.threshold))) threshold = Number(result.threshold);

                if (result.matched) {
                    const score = Math.round(Number(result.score) * 1000) / 10;
                    cameraStatus.className = 'status success';
                    cameraStatus.textContent = `Khớp Face ID: ${result.customer_name} - phòng ${result.room || 'chưa gán'} (độ tương đồng ${score}%).`;
                    return;
                }

                if (result.reason === 'ambiguous_face') {
                    setCameraError(reasonText.ambiguous_face);
                    return;
                }

                cameraStatus.textContent = reasonText[result.reason] || `Chưa khớp, đang thử lại (${attempt}/${maximumAttempts})...`;
                await sleep(450);
            }

            const scoreText = bestScore === null ? '' : ` Điểm tốt nhất ${(bestScore * 100).toFixed(1)}%${threshold === null ? '' : `, ngưỡng ${(threshold * 100).toFixed(1)}%`}.`;
            setCameraError(`Không khớp với Face ID đang lưu.${scoreText}`);
        } catch (error) {
            setCameraError(error.message);
        } finally {
            testing = false;
            testButton.disabled = false;
            registerButton.disabled = false;
            stopButton.disabled = !stream;
        }
    }

    async function cancelEnrollment() {
        enrolling = false;
        const cancelled = sessionId;
        sessionId = null;
        if (cancelled) await jsonRequest(endpoint(root.dataset.cancelUrl, cancelled), { method: 'DELETE' }).catch(() => {});
        registerButton.disabled = false;
        cancelButton.disabled = true;
        stopButton.disabled = !stream;
        progress.value = 0;
        cameraStatus.className = 'status';
        cameraStatus.textContent = 'Đã hủy đăng ký. Không có ảnh nào được lưu.';
    }

    async function refreshHealth() {
        try {
            const data = await jsonRequest(root.dataset.healthUrl);
            setChip('pc-health', `Dịch vụ PC: ${data.pc_online ? 'online' : 'offline'}`, data.pc_online);
            setChip('pi-health', `Raspberry Pi: ${data.pi.online ? 'online' : 'offline'}`, data.pi.online);
            setChip('queue-health', `Đồng bộ chờ: ${data.pending}`, data.pending === 0);
            document.getElementById('active-count').textContent = data.active_profiles;
            document.getElementById('pending-count').textContent = data.pending;
        } catch (_) { setChip('pc-health', 'Không đọc được trạng thái', false); }
    }

    function setChip(id, text, ok) { const chip = document.getElementById(id); chip.textContent = text; chip.className = `chip ${ok ? 'ok' : 'bad'}`; }

    async function runSync(full = false) {
        syncStatus.textContent = full ? 'Đang full sync…' : 'Đang gửi lại các yêu cầu chờ…';
        try {
            const result = await jsonRequest(full ? root.dataset.fullSyncUrl : root.dataset.syncUrl, { method: 'POST' });
            syncStatus.className = 'status success';
            syncStatus.textContent = full ? `Full sync xong: nhận ${result.received}, thêm ${result.added}, cập nhật ${result.updated}, xóa ${result.deleted}.` : `Đã xử lý ${result.processed}, thành công ${result.synced}, lỗi ${result.failed}.`;
        } catch (error) { syncStatus.className = 'status error'; syncStatus.textContent = error.message; }
        await refreshHealth();
    }

    startButton.addEventListener('click', () => startCamera().catch(error => setCameraError(error.message)));
    stopButton.addEventListener('click', stopCamera);
    registerButton.addEventListener('click', registerFace);
    testButton.addEventListener('click', testFace);
    cancelButton.addEventListener('click', cancelEnrollment);
    document.getElementById('sync-now').addEventListener('click', () => runSync(false));
    document.getElementById('full-sync').addEventListener('click', () => runSync(true));
    window.addEventListener('pagehide', () => { if (stream) stream.getTracks().forEach(track => track.stop()); });
    refreshHealth();
    setInterval(refreshHealth, 15000);
})();
