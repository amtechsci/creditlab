<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature Pad</title>
    <style>
        canvas {
            background-color: #fff;
            border: 1px solid #000;
            cursor: crosshair;
            touch-action: none; /* Prevents scrolling while drawing on mobile */
        }
        button {
            margin-top: 10px;
            margin-right: 5px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>

<canvas id="signature-pad" width="400" height="200"></canvas>
<br>
<button id="clear">Clear</button>
<button id="save">Save</button>

<script>
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    let drawing = false;

    // Function to get the correct coordinates
    function getPosition(event) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (event.clientX || event.touches[0].clientX) - rect.left,
            y: (event.clientY || event.touches[0].clientY) - rect.top
        };
    }

    // Function to start drawing
    function startDrawing(event) {
        drawing = true;
        const pos = getPosition(event);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    // Function to draw on the canvas
    function draw(event) {
        if (!drawing) return;
        const pos = getPosition(event);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000';
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    // Function to stop drawing
    function stopDrawing() {
        drawing = false;
        ctx.beginPath();
    }

    // Event listeners for mouse events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);

    // Event listeners for touch events
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
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ image: dataURL })
        }).then(response => response.text()).then(result => {
            alert(result);
        }).catch(error => {
            console.error('Error:', error);
        });
    });
</script>

</body>
</html>
