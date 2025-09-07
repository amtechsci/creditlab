<html>
<style>
    @import url(https://fonts.googleapis.com/css?family=Open+Sans);

    body {
        font-family: "Open Sans", sans-serif;
    }

    .container {
        width: 400px;
        height: 400px;
        margin: 0 auto;
        position: relative;
    }

    #score-dial {
        width: 100%;
        height: 100%;
        position: relative;
        transform: rotateX(180deg);
    }

    .score-band-wrapper {
        position: absolute;
        top: 40%;
        width: 100%;
        text-align: center;
    }

    .score-band-wrapper .score-band-label {
        font-weight: bold;
        font-size: 30px;
        line-height: 20px;
    }

    .score-band-wrapper small {
        display: block;
        font-weight: 300;
        font-size: 16px;
        line-height: 12px;
    }

    .score-dial-wrapper {
        margin: 0 auto;
        top: -40%;
        width: 60%;
        position: relative;
        text-align: center;
    }

    .score-dial-wrapper input {
        width: 100%;
    }

    .legend {
        margin-top: 60px;
    }

    .legend li {
        border-left: 20px solid #fff;
        padding: 10px;
        list-style-type: none;
        font-size: 14px;
    }

    .legend li.excellent {
        border-color: #1f9c4c;
    }

    .legend li.good {
        border-color: #91c738;
    }

    .legend li.fair {
        border-color: #fadd00;
    }

    .legend li.poor {
        border-color: #f79a38;
    }

    .legend li.vpoor {
        border-color: #bd2727;
    }
</style>

<body>
    <div class="container">
        <svg height="500px" width="500px">
            <circle id="red" fill="none" stroke="#bd2727" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="597" />
            <circle class="grey greyRed" fill="none" stroke="#F1F1F1" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="597" />

            <circle id="orange" fill="none" stroke="#f79a38" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="447" />
            <circle class="grey greyOrange" fill="none" stroke="#F1F1F1" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="447" />

            <circle id="yellow" fill="none" stroke="#fadd00" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="297" />
            <circle class="grey greyYellow" fill="none" stroke="#F1F1F1" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="297" />

            <circle id="light-green" fill="none" stroke="#91c738" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="147" />
            <circle class="grey greyLightGreen" fill="none" stroke="#F1F1F1" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="147" />

            <circle id="green" fill="none" stroke="#1f9c4c" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="0" />
            <circle class="grey greyGreen" fill="none" stroke="#F1F1F1" stroke-width="30" cx="200" cy="200" r="140"
                stroke-dasharray="150, 747" stroke-dashoffset="0" />
        </svg>
        <div class="score-band-wrapper">
            <small>Your score band is</small>
            <p class="score-band-label">Excellent</p>
        </div>
        <div class="score-dial-wrapper">
            <input type="range" min="350" max="900" value="350" step="1">
            Your score is
            <p class="score-dial-value">350</p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        const radius = 140;
        const circumference = 2 * Math.PI * radius; // 879.2
        const customCircumference = circumference * 0.85; // 747.32
        const arcLength = customCircumference / 5; // 149.46
        const offset = arcLength * 5;

        /* Updated minimum, maximum values and length for the new 350-900 range */
        const redArc = {
            min: 350.00,
            max: 479.00,
            length: 129.00
        };
        const orangeArc = {
            min: 480.00,
            max: 599.00,
            length: 119.00
        };
        const yellowArc = {
            min: 600.00,
            max: 719.00,
            length: 119.00
        };
        const lightGreenArc = {
            min: 720.00,
            max: 839.00,
            length: 119.00
        };
        const greenArc = {
            min: 840.00,
            max: 900.00,
            length: 60.00
        };

        $(document).ready(function () {
            var scoreBandLabel = $(".score-band-label");

            $('.score-dial-wrapper input').on("change mousemove", function () {
                var score = parseInt($(this).val());
                $(this).next().html(score);

                if (score >= redArc.min) {
                    scoreBandLabel.html("Very Poor");
                    $(".greyRed").attr("stroke-dasharray",
                        arcLength + "," + (customCircumference + ((arcLength * (score - redArc.min)) / redArc.length)));
                }

                if (score >= orangeArc.min) {
                    scoreBandLabel.html("Poor");
                    $(".greyOrange").attr("stroke-dasharray",
                        arcLength + "," + (customCircumference + ((arcLength * (score - orangeArc.min)) / orangeArc.length)));
                }

                if (score >= yellowArc.min) {
                    scoreBandLabel.html("Fair");
                    $(".greyYellow").attr("stroke-dasharray",
                        arcLength + "," + (customCircumference + ((arcLength * (score - yellowArc.min)) / yellowArc.length)));
                }

                if (score >= lightGreenArc.min) {
                    scoreBandLabel.html("Good");
                    $(".greyLightGreen").attr("stroke-dasharray",
                        arcLength + "," + (customCircumference + ((arcLength * (score - lightGreenArc.min)) / lightGreenArc.length)));
                }

                if (score >= greenArc.min) {
                    scoreBandLabel.html("Excellent");
                }

                let greenArchCalc = ((arcLength * (score - greenArc.min)) / greenArc.length);
                $(".greyGreen").attr("stroke-dasharray", (arcLength - greenArchCalc) + "," + customCircumference);
                $('.greyGreen').attr("stroke-dashoffset", -greenArchCalc);
            });

        });
    </script>
</body>

</html>
