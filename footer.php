</div> <!-- END MAIN CONTENT -->

<!-- CALENDAR EVENT ADD MODAL -->
<div id="eventModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--main-blue); margin-top:0; border-bottom: 1px solid #ccc; padding-bottom:10px;">Add New
            Schedule</h3>
        <input type="hidden" id="modal-date">
        <div class="form-group"><label>Calendar Office</label>
            <select id="modal-office">
                <option value="STTI">STTI Office</option>
                <option value="SMILE">SATELLITE OFFICE</option>
            </select>
        </div>
        <div class="form-group"><label>Title</label><input type="text" id="modal-title" required></div>
        <div class="form-group"><label>Start Time</label><input type="time" id="modal-time" value="08:00" required>
        </div>
        <div class="form-group"><label>Notes</label><textarea id="modal-desc" class="note-textarea"
                style="height:60px;"></textarea></div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('eventModal')">Cancel</button>
            <button type="button" class="btn-submit" onclick="saveEvent()" style="margin:0;">Save Schedule</button>
        </div>
    </div>
</div>

<!-- CALENDAR EVENT VIEW MODAL -->
<div id="viewEventModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--main-blue); margin-top:0;">Event Details</h3>
        <input type="hidden" id="view-event-id">
        <h4 id="view-event-title" style="margin-bottom:5px; color:var(--main-blue); font-size:22px;"></h4>
        <p id="view-event-time" style="color:var(--text-muted); font-size:14px; margin-bottom:20px;"></p>
        <div style="background:var(--light-bg); padding:15px; border-radius:8px; font-size:14px; margin-bottom:10px;"
            id="view-event-desc"></div>
        <div class="modal-actions" style="justify-content: space-between;">
            <button type="button" class="btn-danger" onclick="executeDeleteEvent()">Delete</button>
            <button type="button" class="btn-cancel" onclick="closeModal('viewEventModal')">Close</button>
        </div>
    </div>
</div>

<!-- SCHEDULE CONFLICT ALERT MODAL -->
<div id="conflictModal" class="modal-overlay">
    <div class="modal-box" style="width: 380px; text-align: center;">
        <div style="font-size: 45px; margin-bottom: 10px;">⚠️</div>
        <h3 style="color: #ef4444; margin-bottom: 10px; border: none; padding-bottom: 0;">Schedule Conflict</h3>
        <p style="margin-bottom: 25px; font-size: 15px; color: var(--text-main);">There is already an event booked on
            this day and exact time. Please choose a different schedule.</p>
        <div class="modal-actions" style="justify-content: center;">
            <button type="button" class="btn-cancel" onclick="closeModal('conflictModal')"
                style="width: 100%; background: var(--light-bg);">Understood</button>
        </div>
    </div>
</div>

<!-- SATELLITE: CAMERA REQUIRED ALERT MODAL -->
<div id="cameraAlertModal" class="modal-overlay">
    <div class="modal-box" style="width: 380px; text-align: center;">
        <div style="font-size: 45px; margin-bottom: 10px;">📸</div>
        <h3 style="color: #f59e0b; margin-bottom: 10px; border: none; padding-bottom: 0;">Camera Required</h3>
        <p style="margin-bottom: 25px; font-size: 15px; color: var(--text-main);">Pakikuha muna ng litrato (Capture
            Photo) bago mag-submit ng form.</p>
        <div class="modal-actions" style="justify-content: center;">
            <button type="button" class="btn-cancel" onclick="closeModal('cameraAlertModal')"
                style="width: 100%; background: var(--light-bg);">Okay</button>
        </div>
    </div>
</div>

<!-- SATELLITE: ALREADY REGISTERED ALERT MODAL -->
<div id="duplicateUserModal" class="modal-overlay">
    <div class="modal-box" style="width: 380px; text-align: center;">
        <div style="font-size: 45px; margin-bottom: 10px;">🛑</div>
        <h3 style="color: #ef4444; margin-bottom: 10px; border: none; padding-bottom: 0;">Already Registered</h3>
        <p style="margin-bottom: 25px; font-size: 15px; color: var(--text-main);">Nakapag-register na ang pangalang ito.
            Pakigamit na lang ang <strong>Log Attendance</strong> section.</p>
        <div class="modal-actions" style="justify-content: center;">
            <button type="button" class="btn-cancel" onclick="closeModal('duplicateUserModal')"
                style="width: 100%; background: var(--light-bg);">Understood</button>
        </div>
    </div>
</div>

<!-- STAFF MODAL (FIXED OVERFLOW SO TEXT WONT EXCEED) -->
<div id="staffModal" class="modal-overlay">
    <div class="modal-box" style="width: 450px;">
        <h3 style="color: var(--main-blue); margin-top:0;">Staff Assignment</h3>
        <div style="margin-bottom: 10px;"><strong>Name:</strong> <span id="staff-modal-name"></span></div>
        <div style="margin-bottom: 10px;"><strong>Role:</strong> <span id="staff-modal-role"></span></div>
        <div style="margin-bottom: 10px;"><strong>Date:</strong> <span id="staff-modal-date"></span></div>
        <div style="margin-bottom: 10px;"><strong>Task:</strong>
            <div style="background:var(--light-bg); padding:10px; border-radius:6px; margin-top:5px; word-break: break-word; overflow-wrap: break-word; white-space: normal; max-height: 150px; overflow-y: auto;"
                id="staff-modal-task"></div>
        </div>
        <div class="modal-actions"><button type="button" class="btn-cancel"
                onclick="closeModal('staffModal')">Close</button></div>
    </div>
</div>

<!-- ROUTING UPDATE MODAL -->
<div id="routingModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--main-blue); margin-top:0;">Routing Document</h3>
        <div style="margin-bottom: 10px;"><strong>Document:</strong> <span id="route-modal-doc"></span></div>
        <div style="margin-bottom: 10px;"><strong>Assigned To:</strong> <span id="route-modal-assign"></span></div>
        <div style="margin-bottom: 10px;"><strong>Date Routed:</strong> <span id="route-modal-date"></span></div>

        <hr style="border:0; border-top:1px solid #ccc; margin: 20px 0;">

        <form method="POST" action="routing.php">
            <input type="hidden" name="route_update_id" id="route-update-id">
            <div class="form-group">
                <label>Update Status:</label>
                <select name="new_status" id="route-modal-status-select" required>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Approved">Approved</option>
                    <option value="Set">Set</option>
                    <option value="Retrieved">Retrieved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="modal-actions" style="justify-content: space-between;">
                <button type="button" class="btn-cancel" onclick="closeModal('routingModal')">Close</button>
                <button type="submit" name="update_routing_status" class="btn-submit" style="margin:0;">Save
                    Update</button>
            </div>
        </form>
    </div>
</div>

<!-- SATELLITE USER DETAILS MODAL -->
<div id="satelliteModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--main-blue); margin-top:0;">User Identity Record</h3>
        <div style="text-align:center; margin-bottom:15px;">
            <img id="sat-modal-pic" src="" alt="No Photo"
                style="width:150px; height:150px; object-fit:cover; border-radius:8px; border:2px solid var(--accent-blue); display:none;">
        </div>
        <div style="margin-bottom: 10px;"><strong>Name:</strong> <span id="sat-modal-name"></span></div>
        <div style="margin-bottom: 10px;"><strong>Station:</strong> <span id="sat-modal-loc"></span></div>
        <div style="margin-bottom: 10px;"><strong>Contact:</strong> <span id="sat-modal-con"></span></div>
        <div class="modal-actions"><button type="button" class="btn-cancel"
                onclick="closeModal('satelliteModal')">Close</button></div>
    </div>
</div>

<!-- DELETE NOTE MODAL -->
<div id="deleteNoteModal" class="modal-overlay">
    <div class="modal-box" style="width: 380px; text-align: center;">
        <h3 style="color: #ef4444; margin-bottom: 15px; border: none;">Delete Note</h3>
        <p style="margin-bottom: 25px; font-size: 15px;">Are you sure you want to permanently delete this office note?
        </p>
        <form method="POST" action="index.php">
            <input type="hidden" name="delete_note_id" id="delete-note-id-input">
            <div class="modal-actions" style="justify-content: center;">
                <button type="button" class="btn-cancel" onclick="closeModal('deleteNoteModal')">Cancel</button>
                <button type="submit" class="btn-danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
    // CLOCK
    setInterval(() => {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        document.getElementById('hour-min').innerText = h + ':' + (m < 10 ? '0' : '') + m;
        document.getElementById('sec-ampm').innerText = ampm;
        document.getElementById('live-date').innerText = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }, 1000);

    // MODAL TOGGLES
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openViewModal(id, title, timeStr, desc) {
        document.getElementById('view-event-id').value = id;
        document.getElementById('view-event-title').innerText = title;
        document.getElementById('view-event-time').innerText = timeStr;
        document.getElementById('view-event-desc').innerText = desc || "No notes.";
        document.getElementById('viewEventModal').style.display = 'flex';
    }

    function openStaffModal(name, role, task, date) {
        document.getElementById('staff-modal-name').innerText = name;
        document.getElementById('staff-modal-role').innerText = role;
        document.getElementById('staff-modal-task').innerText = task;
        document.getElementById('staff-modal-date').innerText = date;
        document.getElementById('staffModal').style.display = 'flex';
    }

    function openRoutingModal(id, doc, assign, date, status) {
        document.getElementById('route-update-id').value = id;
        document.getElementById('route-modal-doc').innerText = doc;
        document.getElementById('route-modal-assign').innerText = assign;
        document.getElementById('route-modal-date').innerText = date;
        document.getElementById('route-modal-status-select').value = status;
        document.getElementById('routingModal').style.display = 'flex';
    }

    function openSatelliteModal(name, loc, con, picBase64) {
        document.getElementById('sat-modal-name').innerText = name;
        document.getElementById('sat-modal-loc').innerText = loc;
        document.getElementById('sat-modal-con').innerText = con;
        let picEl = document.getElementById('sat-modal-pic');
        if (picBase64 && picBase64.length > 100) {
            picEl.src = picBase64;
            picEl.style.display = 'inline-block';
        } else {
            picEl.style.display = 'none';
        }
        document.getElementById('satelliteModal').style.display = 'flex';
    }

    function openDeleteNoteModal(noteId) {
        document.getElementById('delete-note-id-input').value = noteId;
        document.getElementById('deleteNoteModal').style.display = 'flex';
    }

    // FULLCALENDAR
    let sttiCalendar, smileCalendar;
    document.addEventListener('DOMContentLoaded', function () {
        let config = {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
            selectable: true, eventColor: 'var(--accent-blue)',
            select: function (info) {
                document.getElementById('modal-date').value = info.startStr;
                document.getElementById('eventModal').style.display = 'flex';
            },
            eventClick: function (info) {
                let timeF = info.event.start.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
                openViewModal(info.event.id, info.event.title, timeF, info.event.extendedProps.description);
            }
        };
        if (document.getElementById('calendar-stti')) {
            sttiCalendar = new FullCalendar.Calendar(document.getElementById('calendar-stti'), { ...config, events: 'get_events.php?office=STTI' });
            sttiCalendar.render();
        }
        if (document.getElementById('calendar-smile')) {
            smileCalendar = new FullCalendar.Calendar(document.getElementById('calendar-smile'), { ...config, events: 'get_events.php?office=SMILE' });
            smileCalendar.render();
        }
    });

    // CALENDAR CONFLICT LOGIC (UPDATED: INDEPENDENT CHECKING)
    function saveEvent() {
        let title = document.getElementById('modal-title').value;
        let dateStr = document.getElementById('modal-date').value;
        let timeStr = document.getElementById('modal-time').value;
        let selectedOffice = document.getElementById('modal-office').value; // Kunin ang piniling opisina

        if (!title) { alert("Title is required"); return; }

        let isConflict = false;
        let targetEvents = [];

        // I-check lang ang events ng piniling calendar/office
        if (selectedOffice === 'STTI' && sttiCalendar) {
            targetEvents = sttiCalendar.getEvents();
        } else if (selectedOffice === 'SMILE' && smileCalendar) {
            targetEvents = smileCalendar.getEvents();
        }

        // Loop para hanapin kung may kaparehong date at time sa napiling opisina
        for (let evt of targetEvents) {
            let d = evt.start; if (!d) continue;
            let eY = d.getFullYear(), eM = String(d.getMonth() + 1).padStart(2, '0'), eDay = String(d.getDate()).padStart(2, '0');
            let eH = String(d.getHours()).padStart(2, '0'), eMin = String(d.getMinutes()).padStart(2, '0');

            if (`${eY}-${eM}-${eDay}` === dateStr && `${eH}:${eMin}` === timeStr) {
                isConflict = true;
                break;
            }
        }

        if (isConflict) {
            // Papalabas ang custom modal kapag may conflict sa iisang office
            document.getElementById('conflictModal').style.display = 'flex';
            return;
        }

        let formData = new FormData();
        formData.append('title', title);
        formData.append('event_date', dateStr);
        formData.append('start_time', timeStr);
        formData.append('description', document.getElementById('modal-desc').value);
        formData.append('office_type', selectedOffice);

        fetch('add_calendar_event.php', { method: 'POST', body: formData })
            .then(res => res.text()).then(data => {
                if (data.trim() === "success") { window.location.reload(); } else { alert('Error: ' + data); }
            });
    }

    function executeDeleteEvent() {
        let formData = new FormData();
        formData.append('id', document.getElementById('view-event-id').value);
        fetch('delete_calendar_event.php', { method: 'POST', body: formData })
            .then(res => res.text()).then(data => {
                if (data.trim() === "success") window.location.reload();
            });
    }

    // WEBCAM SCRIPT (STRICT VALIDATION)
    if (document.getElementById('webcam')) {
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('snapshot');
        const preview = document.getElementById('photo-preview');
        let streamActive = null;

        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
                streamActive = stream;
                video.srcObject = stream;
            }).catch(err => console.log(err));
        }

        window.capturePhoto = function () {
            if (!streamActive) { alert("No camera detected."); return; }
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            let imgData = canvas.toDataURL('image/jpeg', 0.8);

            document.querySelectorAll('.shared_photo_input').forEach(input => input.value = imgData);
            preview.src = imgData;
            video.style.display = 'none';
            preview.style.display = 'block';
            document.getElementById('retake-btn').style.display = 'block';
        };

        window.retakePhoto = function () {
            document.querySelectorAll('.shared_photo_input').forEach(input => input.value = "");
            preview.style.display = 'none';
            video.style.display = 'block';
            document.getElementById('retake-btn').style.display = 'none';
        };

        window.validateForm = function () {
            let inputs = document.querySelectorAll('.shared_photo_input');
            let hasPhoto = false;
            inputs.forEach(input => { if (input.value !== "") hasPhoto = true; });

            if (!hasPhoto) {
                // PAPALABAS ANG BEAUTIFUL CAMERA ALERT MODAL!
                document.getElementById('cameraAlertModal').style.display = 'flex';
                return false;
            }
            return true;
        };
    }
</script>

<!-- SCRIPT PARA SA ALREADY REGISTERED PHP POP-UP -->
<?php if (isset($_SESSION['show_duplicate_modal'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById('duplicateUserModal').style.display = 'flex';
        });
    </script>
    <?php unset($_SESSION['show_duplicate_modal']); endif; ?>

</body>

</html>