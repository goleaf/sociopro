<!DOCTYPE html>

<head>
    <title>{{ get_phrase('Live class') }} : {{ get_phrase('Page title') }}</title>
    <meta charset="utf-8" />
    <link type="text/css" rel="stylesheet" href="https://source.zoom.us/2.6.0/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" href="https://source.zoom.us/2.6.0/css/react-select.css" />
    <script src="https://source.zoom.us/1.7.2/lib/vendor/jquery.min.js"></script>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
</head>

<body>
    <style>
        body {
            padding-top: 50px;
        }

        .course_info {
            color: #999999;
            font-size: 11px;
            padding-bottom: 10px;
        }

        .btn-finish {
            background-color: #656565;
            border-color: #222222;
            color: #cacaca;
        }

        .btn-finish:hover,
        .btn-finish:focus,
        .btn-finish:active,
        .btn-finish.active,
        .open .dropdown-toggle.btn-finish {
            color: #cacaca;
        }

        .course_user_info {
            color: #989898;
            font-size: 12px;
            margin-right: 20px;
        }
    </style>

    <script src="https://source.zoom.us/2.6.0/lib/vendor/react.min.js"></script>
    <script src="https://source.zoom.us/2.6.0/lib/vendor/react-dom.min.js"></script>
    <script src="https://source.zoom.us/2.6.0/lib/vendor/redux.min.js"></script>
    <script src="https://source.zoom.us/2.6.0/lib/vendor/redux-thunk.min.js"></script>
    <script src="https://source.zoom.us/2.6.0/lib/vendor/lodash.min.js"></script>
    <script src="https://source.zoom.us/zoom-meeting-2.6.0.min.js"></script>

    <script>
        "use strict";

        function stop_zoom() {
            var r = confirm("{{ get_phrase('Do you want to leave the live video') }} ? {{ get_phrase('You can join them later if the video remains live') }}");
            if (r == true) {
                ZoomMtg.leaveMeeting();
            }

        }

        $(document).ready(function() {
            start_zoom();
        });

        function start_zoom() {

            ZoomMtg.preLoadWasm();
            ZoomMtg.prepareJssdk();

            var API_KEY = "{{$zoom_api_key}}";
            var SIGNATURE = "{{$zoom_signature}}";
            var USER_NAME = "{{Auth()->user()->name}}";
            var MEETING_NUMBER = "{{$meeting_details['id']}}";
            var PASSWORD = "{{$meeting_details['password']}}";


            var leave_url = "@if($host == 1){{route('zoom-meeting-leave-url', $post_details->post_id)}}@else{{ route('timeline') }}@endif";

            var testTool = window.testTool;


            var meetConfig = {
                apiKey: API_KEY,
                signature: SIGNATURE,
                meetingNumber: MEETING_NUMBER,
                userName: USER_NAME,
                passWord: PASSWORD,
                leaveUrl: leave_url,
                role: "{{$host}}"
            };



            ZoomMtg.init({
                leaveUrl: meetConfig.leaveUrl,
                showMeetingHeader: true,
                isSupportAV: {{$isSupportAV}},
                isSupportChat: true,
                disableJoinAudio: {{$disableJoinAudio}},
                success: function() {
                    ZoomMtg.join({
                        meetingNumber: meetConfig.meetingNumber,
                        userName: meetConfig.userName,
                        signature: meetConfig.signature,
                        apiKey: meetConfig.apiKey,
                        passWord: meetConfig.passWord
                    });
                }
            });
        }
    </script>
</body>

</html>
