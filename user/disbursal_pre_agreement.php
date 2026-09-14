<?php
$page_state = (int) ($page_state ?? 0);

if ($page_state === 12) {
?>
        <style>
            canvas { background-color: #fff; border: 1px solid #000; cursor: crosshair; }
            button { margin-top: 10px; margin-right: 5px; padding: 5px 10px; }
        </style>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1>Signature</h1>
                    <h5>Please sign below similar to your signature on PAN Card.</h5>
                    <canvas id="signature-pad" style="width:100%"></canvas>
                    <br>
                    <button id="clear" class="btn btn-danger">Clear & re-sign</button><br>
                    I authorize Creditlab to use my electronic signature for signing loan agreements on my behalf for all future loans.<br>
                    <button id="save" class="btn btn-success">Save and submit</button>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('signature-pad');
            const ctx = canvas.getContext('2d');
            let drawing = false;
            let lastPos = { x: 0, y: 0 };

            function getPosition(event) {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: (event.clientX || event.touches[0].clientX) - rect.left,
                    y: (event.clientY || event.touches[0].clientY) - rect.top
                };
            }

            function startDrawing(event) {
                drawing = true;
                lastPos = getPosition(event);
                ctx.beginPath();
                ctx.moveTo(lastPos.x, lastPos.y);
                event.preventDefault();
            }

            function draw(event) {
                if (!drawing) return;
                const pos = getPosition(event);
                if (pos.x !== lastPos.x || pos.y !== lastPos.y) {
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.strokeStyle = '#000';
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                    lastPos = pos;
                }
                event.preventDefault();
            }

            function stopDrawing() {
                drawing = false;
                ctx.beginPath();
            }

            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            canvas.addEventListener('touchstart', startDrawing);
            canvas.addEventListener('touchmove', draw);
            canvas.addEventListener('touchend', stopDrawing);
            canvas.addEventListener('touchcancel', stopDrawing);

            document.getElementById('clear').addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            });

            document.getElementById('save').addEventListener('click', () => {
                const dataURL = canvas.toDataURL('image/png');
                fetch('save_signature.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image: dataURL })
                }).then(response => response.text()).then(result => {
                    location.reload();
                }).catch(error => console.error('Error:', error));
            });
        });
        </script>
<?php
} elseif ($page_state === 13) {
?>
        <div class="container">
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-6">
                    <h1>Video KYC</h1>
                    <h5>Kindly upload a short video by pronouncing the below sentence & submit.</h5>
                    “I AM APPLYING LOAN AT CREDITLAB WITH MY KNOWLEDGE“
                    <video id="videoElement" style="width: 100%; height: 100%;" playsinline webkit-playsinline></video>
                    <div id="countdown"></div>
                    <button id="start-btn" class="btn btn-primary">Take Video / Retake Video</button>
                    <p style="color:red;">Note*<br>
                        LOOK at the camera in such a way that your complete face is covered in the video<br>
                        Don’t wear cap 🧢 <br>
                        Don’t wear spects</p>
                    <button id="upload-btn" class="btn btn-success" disabled>Submit</button>
                </div>
            </div>
        </div>
        <script>
        const video = document.querySelector('#videoElement');
        const startBtn = document.querySelector('#start-btn');
        const uploadBtn = document.querySelector('#upload-btn');
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const constraints = { audio: true, video: { width: 640, height: 480, facingMode: 'user' } };
        let mediaRecorder;
        let chunks = [];
        let blob;

        function startRecording() {
            var timeleft = 10;
            var downloadTimer = setInterval(function(){
                if(timeleft <= 0){
                    clearInterval(downloadTimer);
                    document.getElementById("countdown").innerHTML = "Finished";
                } else {
                    document.getElementById("countdown").innerHTML = timeleft + " seconds remaining";
                }
                timeleft -= 1;
            }, 1000);

            navigator.mediaDevices.getUserMedia(constraints).then(stream => {
                video.srcObject = stream;
                if (isIOS) {
                    video.setAttribute('playsinline', true);
                    video.setAttribute('webkit-playsinline', true);
                }
                video.play();
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.start();
                setTimeout(stopRecording, 10000);
                mediaRecorder.addEventListener('dataavailable', event => chunks.push(event.data));
            }).catch(error => {
                console.error("Error accessing media devices.", error);
                alert("Unable to access camera or microphone. Please check your permissions.");
            });
        }

        function stopRecording() {
            uploadBtn.disabled = false;
            startBtn.disabled = false;
            mediaRecorder.stop();
            video.pause();
            if (isIOS && video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
                video.srcObject = null;
            }
        }

        function uploadRecording() {
            blob = new Blob(chunks, { type: 'video/mp4' });
            const formData = new FormData();
            formData.append('video', blob, 'video.mp4');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/zzz.php');
            xhr.onload = () => {
                console.log(xhr.responseText);
                if (xhr.status === 200 && xhr.responseText == 1) {
                    window.location.replace('index.php');
                } else {
                    console.log('Error uploading video.');
                }
            };
            xhr.send(formData);
        }

        startBtn.addEventListener('click', () => {
            startBtn.disabled = true;
            chunks = [];
            startRecording();
        });

        uploadBtn.addEventListener('click', () => {
            uploadBtn.disabled = true;
            startBtn.disabled = false;
            uploadRecording();
        });
        </script>
<?php
} elseif ($page_state === 14) {
    include 'easebuzz.php';
}
