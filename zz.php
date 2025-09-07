<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Record and upload a 5-second video</title>
</head>
<body>
  <h1>Record and upload a 5-second video</h1>
  <video></video>
  <button id="start-btn">Start recording</button>
  <button id="upload-btn" disabled>Upload recording</button>

  <script>
    // Get the video element and buttons
    const video = document.querySelector('video');
    const startBtn = document.querySelector('#start-btn');
    const uploadBtn = document.querySelector('#upload-btn');

    // Constraints for capturing video
    const constraints = {
      audio: true,
      video: {
        width: 640,
        height: 480
      }
    };

    // Variables for storing the recorded media
    let mediaRecorder;
    let chunks = [];
    let blob;

    // Start recording the video
    function startRecording() {
      // Request access to the user's webcam
      navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
          // Show the video stream in the video element
          video.srcObject = stream;
          video.play();

          // Create a MediaRecorder instance and start recording
          mediaRecorder = new MediaRecorder(stream);
          mediaRecorder.start();

          // Set a timer to stop recording after 5 seconds
          setTimeout(stopRecording, 5000);

          // Listen for dataavailable events and add the chunks to the array
          mediaRecorder.addEventListener('dataavailable', event => {
            chunks.push(event.data);
          });
        })
        .catch(error => console.log(error));
    }

    // Stop recording the video and upload it to the server
    function stopRecording() {
      uploadBtn.disabled = false;
      startBtn.disabled = false;
      mediaRecorder.stop();
      video.pause();
    }
    function uploadRecording() {
      blob = new Blob(chunks, { type: 'video/mp4' });
      const formData = new FormData();
      formData.append('video', blob, 'video.mp4');
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'zzz.php');
      xhr.onload = () => {
        if (xhr.status === 200) {
          console.log(xhr.responseText);
        } else {
          console.log('Error uploading video.');
        }
      };
      xhr.send(formData);
    }

    // Attach event listeners to the buttons
    startBtn.addEventListener('click', () => {
      startBtn.disabled = true;
      startRecording();
    });
    uploadBtn.addEventListener('click', () => {
      uploadBtn.disabled = true;
      startBtn.disabled = false;
      uploadRecording();
    });
  </script>
</body>
</html>
