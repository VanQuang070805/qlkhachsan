(() => {
    const root = document.getElementById('face-id-app');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const video = document.getElementById('laptop-camera');
    const cameraStatus = document.getElementById('camera-status');
    const syncStatus = document.getElementById('sync-status');
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const testButton = document.getElementById('test-face');
    let stream = null;
    let testing = false;

    async function jsonRequest(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Yêu cầu không thành công.');
        return data;
    }

    async function startCamera() {
        if (stream) return;
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            throw new Error('Hãy mở trang bằng localhost hoặc HTTPS để dùng camera.');
        }
        stream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 960 }, height: { ideal: 540 }, facingMode: 'user' },
            audio: false,
        });
        video.srcObject = stream;
        video.hidden = false;
        await video.play();
        startButton.disabled = true;
        stopButton.disabled = false;
        cameraStatus.className = 'status';
        cameraStatus.textContent = 'Camera đã sẵn sàng.';
    }

    function stopCamera() {
        if (testing) return;
        stream?.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        startButton.disabled = false;
        stopButton.disabled = true;
        cameraStatus.className = 'status';
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
            canvas.toBlob(
                blob => blob ? resolve(blob) : reject(new Error('Không chụp được khung hình.')),
                'image/jpeg',
                0.86,
            );
        });
    }

    async function testFace() {
        try {
            await startCamera();
            testing = true;
            testButton.disabled = true;
            stopButton.disabled = true;
            cameraStatus.className = 'status';
            cameraStatus.textContent = 'Đang nhận diện khuôn mặt...';

            const form = new FormData();
            form.append('frame', await captureFrame(), 'frame.jpg');
            const result = await jsonRequest(root.dataset.recognizeUrl, { method: 'POST', body: form });
            const score = Number(result.score);
            const scoreText = Number.isFinite(score) ? ` · độ khớp ${(score * 100).toFixed(1)}%` : '';
            const details = [
                result.room ? `phòng ${result.room}` : null,
                result.guest_cccd ? `CCCD ${result.guest_cccd}` : null,
                result.guest_phone ? `SĐT ${result.guest_phone}` : null,
            ].filter(Boolean).join(' · ');
            cameraStatus.className = 'status success';
            cameraStatus.textContent = `Nhận diện: ${result.customer_name}${details ? ` · ${details}` : ''}${scoreText}.`;
        } catch (error) {
            cameraStatus.className = 'status error';
            cameraStatus.textContent = error.message;
        } finally {
            testing = false;
            testButton.disabled = false;
            stopButton.disabled = !stream;
        }
    }

    async function refreshHealth() {
        try {
            const health = await jsonRequest(root.dataset.healthUrl);
            setChip('pc-health', health.pc_online, health.pc_online ? 'Dịch vụ PC: online' : 'Dịch vụ PC: offline');
            setChip('pi-health', health.pi?.online, health.pi?.online ? 'Raspberry Pi: online' : 'Raspberry Pi: offline');
            document.getElementById('active-count').textContent = health.active_profiles ?? 0;
            document.getElementById('pending-count').textContent = health.pending ?? 0;
            const queue = document.getElementById('queue-health');
            queue.className = `chip ${Number(health.pending) ? 'bad' : 'ok'}`;
            queue.textContent = `Đồng bộ: ${health.pending ?? 0} chờ gửi`;
        } catch {
            setChip('pc-health', false, 'Dịch vụ PC: không phản hồi');
            setChip('pi-health', false, 'Raspberry Pi: không phản hồi');
        }
    }

    function setChip(id, online, text) {
        const chip = document.getElementById(id);
        chip.className = `chip ${online ? 'ok' : 'bad'}`;
        chip.textContent = text;
    }

    async function runSync(url, label) {
        try {
            syncStatus.className = 'status';
            syncStatus.textContent = `${label}...`;
            const result = await jsonRequest(url, { method: 'POST' });
            syncStatus.className = 'status success';
            syncStatus.textContent = result.message || `Đã xử lý ${result.processed ?? 0} yêu cầu.`;
            await refreshHealth();
        } catch (error) {
            syncStatus.className = 'status error';
            syncStatus.textContent = error.message;
        }
    }

    startButton.addEventListener('click', () => startCamera().catch(error => {
        cameraStatus.className = 'status error';
        cameraStatus.textContent = error.message;
    }));
    stopButton.addEventListener('click', stopCamera);
    testButton.addEventListener('click', testFace);
    document.getElementById('sync-now').addEventListener('click', () => runSync(root.dataset.syncUrl, 'Đang retry đồng bộ'));
    document.getElementById('full-sync').addEventListener('click', () => runSync(root.dataset.fullSyncUrl, 'Đang full sync'));
    window.addEventListener('beforeunload', () => stream?.getTracks().forEach(track => track.stop()));
    refreshHealth();
})();
