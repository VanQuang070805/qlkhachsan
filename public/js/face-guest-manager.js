(() => {
    const root = document.getElementById('face-guest-manager');
    if (!root) return;

    const modalElement = document.getElementById('faceGuestModal');
    const roomLabel = document.getElementById('face-room-label');
    const list = document.getElementById('face-guest-list');
    const count = document.getElementById('face-guest-count');
    const nameInput = document.getElementById('face-guest-name');
    const cccdInput = document.getElementById('face-guest-cccd');
    const phoneInput = document.getElementById('face-guest-phone');
    const consentInput = document.getElementById('face-guest-consent');
    const video = document.getElementById('face-guest-camera');
    const status = document.getElementById('face-guest-status');
    const progress = document.getElementById('face-sample-progress');
    const enrollmentControls = document.getElementById('face-enrollment-controls');
    const formTitle = document.getElementById('face-form-title');
    const startButton = document.getElementById('face-start-camera');
    const registerButton = document.getElementById('face-register-guest');
    const saveButton = document.getElementById('face-save-guest');
    const cancelEditButton = document.getElementById('face-cancel-edit');
    const cancelEnrollmentButton = document.getElementById('face-cancel-enrollment');
    const stopButton = document.getElementById('face-stop-camera');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let context = null;
    let profiles = [];
    let editingId = null;
    let stream = null;
    let sessionId = null;
    let enrolling = false;

    const sleep = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));
    const endpoint = (template, marker, value) => template.replace(marker, encodeURIComponent(value));

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
        if (!response.ok) throw new Error(data.message || firstValidationError(data.errors) || 'Yêu cầu không thành công.');
        return data;
    }

    function firstValidationError(errors) {
        if (!errors || typeof errors !== 'object') return '';
        const first = Object.values(errors).flat()[0];
        return typeof first === 'string' ? first : '';
    }

    function selectedRoomContext() {
        const room = typeof window.getSelectedFaceGuestRoom === 'function'
            ? window.getSelectedFaceGuestRoom()
            : null;
        if (!room) return null;
        return {
            bookingId: room.active_booking_id,
            roomId: room.id,
            roomNumber: room.room_number,
            customerName: room.customer_name,
            customerPhone: room.customer_phone,
        };
    }

    function normalizeContext(value) {
        if (!value) return null;
        const bookingId = Number(value.bookingId);
        const roomId = Number(value.roomId);
        if (!Number.isInteger(bookingId) || !Number.isInteger(roomId)) return null;
        return {
            bookingId,
            roomId,
            roomNumber: value.roomNumber || '',
            customerName: value.customerName || '',
            customerPhone: value.customerPhone || '',
        };
    }

    window.openFaceGuestManager = async options => {
        context = normalizeContext(options) || normalizeContext(selectedRoomContext());
        if (!context) {
            notify('Phòng chưa có booking đang check-in.', 'bg-warning text-dark');
            return;
        }

        resetForm();
        roomLabel.textContent = context.roomNumber ? `Phòng ${context.roomNumber}` : 'Đang tải thông tin phòng...';
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
        await loadProfiles(true);
    };

    async function loadProfiles(prefill = false) {
        renderMessage('Đang tải danh sách khách...');
        try {
            const url = new URL(root.dataset.profilesUrl, window.location.origin);
            url.searchParams.set('booking_id', context.bookingId);
            url.searchParams.set('room_id', context.roomId);
            const data = await jsonRequest(url);
            profiles = Array.isArray(data.profiles) ? data.profiles : [];
            context.roomNumber = data.room_number || context.roomNumber;
            context.customerName = data.booking_customer_name || context.customerName;
            context.customerPhone = data.booking_customer_phone || context.customerPhone;
            roomLabel.textContent = `Phòng ${context.roomNumber || '---'} · ${profiles.length} khách đã đăng ký`;
            renderProfiles();
            if (prefill && !profiles.length && !editingId) {
                nameInput.value = context.customerName;
                phoneInput.value = context.customerPhone;
            }
        } catch (error) {
            renderMessage(error.message, true);
            setStatus(error.message, 'danger');
        }
    }

    function renderProfiles() {
        list.replaceChildren();
        count.textContent = `${profiles.length} khách`;
        if (!profiles.length) {
            renderMessage('Chưa có khách nào đăng ký Face ID.');
            return;
        }

        profiles.forEach(profile => {
            const row = document.createElement('tr');
            row.appendChild(cell(profile.guest_name || 'Chưa đặt tên'));
            row.appendChild(cell(profile.guest_cccd || '---'));
            row.appendChild(cell(profile.guest_phone || '---'));

            const actions = document.createElement('td');
            actions.className = 'face-table-actions';
            const edit = actionButton('fa-pen', 'Sửa thông tin khách', 'btn-outline-primary');
            edit.addEventListener('click', () => beginEdit(profile));
            const remove = actionButton('fa-trash', 'Xóa khách và Face ID', 'btn-outline-danger');
            remove.addEventListener('click', () => deleteProfile(profile));
            actions.append(edit, document.createTextNode(' '), remove);
            row.appendChild(actions);
            list.appendChild(row);
        });
    }

    function cell(value) {
        const element = document.createElement('td');
        element.textContent = value;
        return element;
    }

    function actionButton(icon, title, className) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-sm ${className}`;
        button.title = title;
        button.setAttribute('aria-label', title);
        const iconElement = document.createElement('i');
        iconElement.className = `fa-solid ${icon}`;
        button.appendChild(iconElement);
        return button;
    }

    function renderMessage(message, danger = false) {
        const row = document.createElement('tr');
        const value = document.createElement('td');
        value.colSpan = 4;
        value.className = `text-center py-4 ${danger ? 'text-danger' : 'text-muted'}`;
        value.textContent = message;
        row.appendChild(value);
        list.replaceChildren(row);
        if (!danger) count.textContent = '0 khách';
    }

    function validateGuest() {
        const guest = {
            guest_name: nameInput.value.trim(),
            guest_cccd: cccdInput.value.trim(),
            guest_phone: phoneInput.value.trim(),
        };
        if (!guest.guest_name) throw new Error('Vui lòng nhập họ tên khách.');
        if (guest.guest_cccd && !/^\d{9,12}$/.test(guest.guest_cccd)) {
            throw new Error('Số CCCD phải gồm từ 9 đến 12 chữ số.');
        }
        return guest;
    }

    function beginEdit(profile) {
        if (enrolling) return;
        editingId = profile.id;
        stopCamera();
        nameInput.value = profile.guest_name || '';
        cccdInput.value = profile.guest_cccd || '';
        phoneInput.value = profile.guest_phone || '';
        formTitle.textContent = 'Sửa thông tin khách';
        enrollmentControls.classList.add('d-none');
        startButton.classList.add('d-none');
        registerButton.classList.add('d-none');
        cancelEnrollmentButton.classList.add('d-none');
        stopButton.classList.add('d-none');
        saveButton.classList.remove('d-none');
        cancelEditButton.classList.remove('d-none');
        setStatus('Đang sửa thông tin khách. Khuôn mặt đã lưu được giữ nguyên.');
        nameInput.focus();
    }

    function resetForm() {
        editingId = null;
        nameInput.value = '';
        cccdInput.value = '';
        phoneInput.value = '';
        consentInput.checked = false;
        formTitle.textContent = 'Thêm khách và khuôn mặt';
        enrollmentControls.classList.remove('d-none');
        startButton.classList.remove('d-none');
        registerButton.classList.remove('d-none');
        cancelEnrollmentButton.classList.remove('d-none');
        stopButton.classList.remove('d-none');
        saveButton.classList.add('d-none');
        cancelEditButton.classList.add('d-none');
        updateProgress(0, 15);
        setStatus('Điền thông tin khách và bật camera.');
    }

    async function saveProfile() {
        if (!editingId) return;
        try {
            const guest = validateGuest();
            saveButton.disabled = true;
            const url = endpoint(root.dataset.updateUrl, '__PROFILE__', editingId);
            const result = await jsonRequest(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(guest),
            });
            notify(result.message || 'Đã cập nhật thông tin khách.', 'bg-success');
            resetForm();
            await loadProfiles();
        } catch (error) {
            setStatus(error.message, 'danger');
        } finally {
            saveButton.disabled = false;
        }
    }

    async function deleteProfile(profile) {
        if (!confirm(`Xóa khách "${profile.guest_name}" và vô hiệu hóa Face ID này?`)) return;
        try {
            const url = endpoint(root.dataset.deleteUrl, '__PROFILE__', profile.id);
            const result = await jsonRequest(url, { method: 'DELETE' });
            if (editingId === profile.id) resetForm();
            notify(result.message || 'Đã xóa khách.', 'bg-success');
            await loadProfiles();
        } catch (error) {
            setStatus(error.message, 'danger');
        }
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
        setStatus('Camera đã sẵn sàng.');
    }

    function stopCamera() {
        if (enrolling) return;
        stream?.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        startButton.disabled = false;
        stopButton.disabled = true;
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

    const reasonText = {
        no_face: 'Không thấy khuôn mặt. Nhìn thẳng vào camera.',
        multiple_faces: 'Chỉ để một người trong khung hình.',
        face_too_small: 'Đưa khuôn mặt lại gần camera hơn.',
        too_soon: 'Giữ yên trong giây lát.',
        ambiguous_face: 'Khuôn mặt trùng với nhiều hồ sơ đã lưu.',
    };

    async function registerGuest() {
        try {
            const guest = validateGuest();
            if (!consentInput.checked) throw new Error('Cần xác nhận sự đồng ý của khách.');
            await startCamera();
            enrolling = true;
            registerButton.disabled = true;
            cancelEnrollmentButton.disabled = false;
            stopButton.disabled = true;
            updateProgress(0, 15);
            setStatus('Đang tạo phiên đăng ký...');

            const created = await jsonRequest(root.dataset.createSessionUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: context.bookingId,
                    room_id: context.roomId,
                    consent: true,
                    ...guest,
                }),
            });
            sessionId = created.session_id;
            if (!sessionId) throw new Error('Không tạo được phiên đăng ký Face ID.');

            const target = Number(created.target || 15);
            while (enrolling && sessionId) {
                const form = new FormData();
                form.append('frame', await captureFrame(), 'frame.jpg');
                const url = endpoint(root.dataset.sampleUrl, '__SESSION__', sessionId);
                const result = await jsonRequest(url, { method: 'POST', body: form });
                const samples = Number(result.samples || 0);
                const sampleTarget = Number(result.target || target);
                updateProgress(samples, sampleTarget);
                setStatus(result.accepted
                    ? `Đã lấy ${samples}/${sampleTarget} mẫu khuôn mặt.`
                    : (reasonText[result.reason] || `Đang lấy mẫu ${samples}/${sampleTarget}.`));

                if (result.complete) {
                    enrolling = false;
                    sessionId = null;
                    setStatus('Đăng ký khách và Face ID thành công.', 'success');
                    notify('Đăng ký khách thành công.', 'bg-success');
                    await loadProfiles();
                    nameInput.value = '';
                    cccdInput.value = '';
                    phoneInput.value = '';
                    consentInput.checked = false;
                    break;
                }
                await sleep(220);
            }
        } catch (error) {
            setStatus(error.message, 'danger');
            if (sessionId) await cancelEnrollment();
        } finally {
            enrolling = false;
            registerButton.disabled = false;
            cancelEnrollmentButton.disabled = true;
            stopButton.disabled = !stream;
        }
    }

    async function cancelEnrollment() {
        enrolling = false;
        const activeSession = sessionId;
        sessionId = null;
        if (activeSession) {
            const url = endpoint(root.dataset.cancelUrl, '__SESSION__', activeSession);
            await jsonRequest(url, { method: 'DELETE' }).catch(() => {});
        }
        cancelEnrollmentButton.disabled = true;
        registerButton.disabled = false;
        stopButton.disabled = !stream;
        setStatus('Đã hủy phiên đăng ký.');
    }

    function updateProgress(value, maximum) {
        const safeMaximum = Math.max(1, Number(maximum) || 15);
        const safeValue = Math.max(0, Math.min(Number(value) || 0, safeMaximum));
        progress.style.width = `${(safeValue / safeMaximum) * 100}%`;
        progress.setAttribute('aria-valuemax', String(safeMaximum));
        progress.setAttribute('aria-valuenow', String(safeValue));
    }

    function setStatus(message, type = 'muted') {
        status.className = `face-status small mb-2 text-${type}`;
        status.textContent = message;
    }

    function notify(message, background) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, background);
        }
    }

    startButton.addEventListener('click', () => startCamera().catch(error => setStatus(error.message, 'danger')));
    stopButton.addEventListener('click', stopCamera);
    registerButton.addEventListener('click', registerGuest);
    cancelEnrollmentButton.addEventListener('click', cancelEnrollment);
    saveButton.addEventListener('click', saveProfile);
    cancelEditButton.addEventListener('click', resetForm);
    modalElement.addEventListener('hidden.bs.modal', async () => {
        if (sessionId) await cancelEnrollment();
        enrolling = false;
        stopCamera();
        resetForm();
    });
})();
