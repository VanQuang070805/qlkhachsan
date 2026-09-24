(() => {
    const video = document.getElementById('laptop-camera');
    const status = document.getElementById('camera-status');
    const start = document.getElementById('start-camera');
    const stop = document.getElementById('stop-camera');
    let stream = null;
    let generation = 0;

    function stopCamera() {
        generation++;
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        start.disabled = false;
        stop.disabled = true;
        status.textContent = 'Camera đã tắt. Chưa lưu ảnh hoặc đăng ký khuôn mặt.';
    }

    async function getPreferredCameraStream() {
        const initial = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        const cameras = (await navigator.mediaDevices.enumerateDevices())
            .filter(device => device.kind === 'videoinput');
        const preferred = cameras[1] || cameras[0];

        if (!preferred?.deviceId) return initial;

        initial.getTracks().forEach(track => track.stop());
        return navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: preferred.deviceId } },
            audio: false,
        });
    }

    start.addEventListener('click', async () => {
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            status.textContent = 'Hãy mở web bằng http://localhost:8000 hoặc HTTPS để dùng camera.';
            return;
        }
        const request = ++generation;
        start.disabled = true;
        stop.disabled = false;
        status.textContent = 'Đang chờ quyền truy cập camera…';
        try {
            const acquired = await getPreferredCameraStream();
            if (request !== generation) {
                acquired.getTracks().forEach(track => track.stop());
                return;
            }
            stream = acquired;
            video.srcObject = stream;
            video.hidden = false;
            await video.play();
            if (request !== generation) return;
            status.textContent = 'Camera laptop đang hoạt động — chỉ xem thử, chưa lưu dữ liệu.';
            stream.getVideoTracks().forEach(track => track.addEventListener('ended', stopCamera));
        } catch (error) {
            if (request !== generation) return;
            stopCamera();
            status.textContent = ({
                NotAllowedError: 'Bạn chưa cấp quyền camera. Cho phép camera trong trình duyệt rồi thử lại.',
                NotFoundError: 'Không tìm thấy camera trên máy tính.',
                NotReadableError: 'Không mở được camera. Hãy đóng ứng dụng khác đang dùng camera.',
            })[error.name] || 'Không mở được camera. Kiểm tra thiết bị và quyền của trình duyệt.';
        }
    });
    stop.addEventListener('click', stopCamera);
    window.addEventListener('pagehide', stopCamera);
    document.addEventListener('visibilitychange', () => { if (document.hidden) stopCamera(); });
})();
