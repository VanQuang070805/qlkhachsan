<div id="face-guest-manager"
     data-profiles-url="{{ route('staff.face-id.profiles.index') }}"
     data-update-url="{{ route('staff.face-id.profiles.update', ['profile' => '__PROFILE__']) }}"
     data-delete-url="{{ route('staff.face-id.profiles.delete', ['profile' => '__PROFILE__']) }}"
     data-create-session-url="{{ route('staff.face-id.sessions.create') }}"
     data-sample-url="{{ route('staff.face-id.samples.store', ['sessionId' => '__SESSION__']) }}"
     data-cancel-url="{{ route('staff.face-id.sessions.cancel', ['sessionId' => '__SESSION__']) }}">
    <div class="modal fade" id="faceGuestModal" tabindex="-1" aria-labelledby="faceGuestModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header border-bottom py-3 px-4">
                    <button class="face-window-close" type="button" data-bs-dismiss="modal" aria-label="Đóng"><span>×</span></button>
                    <div class="flex-grow-1 text-center">
                        <h2 class="modal-title h6 fw-bold mb-0" id="faceGuestModalLabel">Đăng ký Face ID khách</h2>
                        <div class="small text-secondary" id="face-room-label">Chọn phòng đang lưu trú</div>
                    </div>
                    <span style="width:14px" aria-hidden="true"></span>
                </div>
                <div class="modal-body p-4">
                    <div class="face-guest-layout">
                        <section>
                            <h3 class="h6 fw-bold mb-3" id="face-form-title">Thêm khách và khuôn mặt</h3>
                            <label class="form-label small fw-medium text-secondary" for="face-guest-name">Họ tên khách</label>
                            <input class="form-control mb-3" id="face-guest-name" maxlength="255" autocomplete="name" placeholder="Ví dụ: Nguyễn Văn A">
                            <div class="face-guest-fields mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium text-secondary" for="face-guest-cccd">CCCD (không bắt buộc)</label>
                                    <input class="form-control" id="face-guest-cccd" maxlength="12" inputmode="numeric" autocomplete="off" placeholder="12 số căn cước">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium text-secondary" for="face-guest-phone">Điện thoại</label>
                                    <input class="form-control" id="face-guest-phone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="09xxxxxxxx">
                                </div>
                            </div>
                            <div id="face-enrollment-controls">
                                <div class="face-camera-preview mb-3">
                                    <video id="face-guest-camera" autoplay muted playsinline hidden aria-label="Camera đăng ký Face ID"></video>
                                    <span>Camera đăng ký đang tắt</span>
                                </div>
                                <label class="form-check mb-3">
                                    <input class="form-check-input" id="face-guest-consent" type="checkbox">
                                    <span class="form-check-label small text-secondary">Khách đã đồng ý lưu Face ID trong thời gian lưu trú.</span>
                                </label>
                                <div class="progress mb-2" style="height:8px; border-radius: 999px;">
                                    <div id="face-sample-progress" class="progress-bar bg-primary" role="progressbar" style="width:0" aria-valuemin="0" aria-valuemax="15" aria-valuenow="0"></div>
                                </div>
                            </div>
                            <p id="face-guest-status" class="face-status small text-secondary mb-3" role="status" aria-live="polite">Điền thông tin và chỉ tiếp tục khi khách đồng ý.</p>
                            <div class="iot-actions face-guest-actions">
                                <button class="btn btn-outline-secondary" id="face-start-camera" type="button">Bật camera</button>
                                <button class="btn btn-primary" id="face-register-guest" type="button">Đăng ký khách</button>
                                <button class="btn btn-primary d-none" id="face-save-guest" type="button">Lưu thông tin</button>
                                <button class="btn btn-outline-secondary d-none" id="face-cancel-edit" type="button">Hủy sửa</button>
                                <button class="btn btn-outline-danger" id="face-cancel-enrollment" type="button" disabled>Hủy đăng ký</button>
                                <button class="btn btn-outline-secondary" id="face-stop-camera" type="button" disabled>Tắt camera</button>
                            </div>
                        </section>
                        <section>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h6 fw-bold mb-0">Khách đã đăng ký</h3>
                                <span class="badge text-bg-secondary" id="face-guest-count">0 khách</span>
                            </div>
                            <div class="face-guest-list-wrap border rounded-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Khách</th>
                                            <th>CCCD</th>
                                            <th>Điện thoại</th>
                                            <th class="text-end">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="face-guest-list">
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary py-4">Chưa có dữ liệu.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
