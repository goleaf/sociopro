
<div id="jitsiMeet"></div>
<?php
    $jitsis = json_decode(get_settings('zitsi_configuration'), true);
?>

<script src="{{ asset('assets/frontend/js/jquery-3.6.0.min.js') }}" charset="utf-8"></script>
{{-- <script src="{{ asset('assets/frontend/js/jitsi.js') }}"></script> --}}

<script>
    const domain = "8x8.vc";
</script>
<script src='https://8x8.vc/<?php echo get_settings('jitsi_app_id'); ?>/external_api.js' async></script>

@php
  $leaveUrl = route('timeline');

@endphp


<!-- check moderator or Auidence -->
@if (auth()->user()->email == $user->email)
    <script type="text/javascript">
        window.onload = () => {
            var email = "{{ $user->email }}";
            var name = "{{ $user->name }}";
            var join_pass = "{{ $join_pass }}";
            var room = "{{ $room }}";
            var jitsi_app_id = "<?php echo $jitsis['jitsi_app_id']; ?>";
            var jitsi_jwt = "<?php echo $jitsis['jitsi_jwt']; ?>";
           
           
                const api = new JitsiMeetExternalAPI(domain, {
                roomName: jitsi_app_id + '/' + room,
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#jitsiMeet'),
                // Make sure to include a JWT if you intend to record,
                // make outbound calls or use any other premium features!
                jwt: jitsi_jwt,

                devices: {
                    audioInput: '<deviceLabel>',
                    audioOutput: '<deviceLabel>',
                    videoInput: '<deviceLabel>'
                },
                configOverwrite: {
                    startWithAudioMuted: true,
                    startWithVideoMuted: true,
                    prejoinPageEnabled: false,
                    remoteVideoMenu: {
                        disableKick: false,
                    },
                    disableRemoteMute: false,
                    toolbarButtons: [
                        'camera',
                        'chat',
                        'closedcaptions',
                        'desktop',
                        'download',
                        'embedmeeting',
                        'etherpad',
                        'feedback',
                        'filmstrip',
                        'fullscreen',
                        'hangup',
                        'help',
                        'invite',
                        'livestreaming',
                        'microphone',
                        'mute-everyone',
                        'mute-video-everyone',
                        'participants-pane',
                        'profile',
                        'raisehand',
                        'recording',
                        'security',
                        'select-background',
                        'settings',
                        'shareaudio',
                        'sharedvideo',
                        'shortcuts',
                        'stats',
                        'tileview',
                        'toggle-camera',
                        'videoquality',
                        '__end'
                    ],
                    // allow:[camera], 
                },
                videoInputerfaceConfigOverwrite: {
                    DISABLE_DOMINANT_SPEAKER_INDICATOR: true
                },
                userInfo: {
                    moderator: true,
                    email: email,
                    displayName: name
                },
                interfaceConfig: {
                    CONNECTION_DISCONNECTED_URL: '<?php echo $leaveUrl; ?>',
                },

            });
            // Redirect users to $leaveUrl upon closing the meeting
            api.on('readyToClose', () => {
                window.location.href = '<?php echo $leaveUrl; ?>';
            });
        }
        //var api = new JitsiMeetExternalAPI(domain, options);

        // set new password for channel
        api.addEventListener('participantRoleChanged', function(event) {
            if (event.role === "moderator") {
                api.executeCommand('password', join_pass);
            }
        });

       // api.executeCommand('subject', 'Streaming Live');

        // api.on('readyToClose', () => {
        //     window.close();
        // });
       
    </script>
@else
    <script type="text/javascript">
        var email = "{{ auth()->user()->email }}";
        var name = "{{  auth()->user()->name }}";
        var join_pass = "{{ $join_pass }}";
        var room = "{{ $room }}";
        var jitsi_app_id = "<?php echo $jitsis['jitsi_app_id']; ?>";
       

        window.onload = () => {
            const api = new JitsiMeetExternalAPI(domain, {
                roomName: jitsi_app_id + '/' + room,
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#jitsiMeet'),
                devices: {
                    audioInput: '<deviceLabel>',
                    audioOutput: '<deviceLabel>',
                    videoInput: '<deviceLabel>'
                },
                configOverwrite: {
                    startWithAudioMuted: true,
                    startWithVideoMuted: true,
                    prejoinPageEnabled: false,
                    remoteVideoMenu: {
                        disableKick: true,
                    },
                    desktopSharingFirefoxDisabled: false,
                    desktopSharingChromeDisabled: false,
                    disableRemoteMute: true,
                    disableInviteFunctions: true,
                    toolbarButtons: [
                        'camera',
                        'chat',
                        'desktop',
                        'download',
                        'etherpad',
                        'filmstrip',
                        'fullscreen',
                        'hangup',
                        'livestreaming',
                        'microphone',
                        'participants-pane',
                        'profile',
                        'raisehand',
                        'recording',
                        'select-background',
                        'settings',
                        'shareaudio',
                        'sharedvideo',
                        'shortcuts',
                        'stats',
                        'tileview',
                        'toggle-camera',
                        'videoquality',
                        '__end'
                    ]
                },
                videoInputerfaceConfigOverwrite: {
                    DISABLE_DOMINANT_SPEAKER_INDICATOR: true
                },
                userInfo: {
                    moderator: false,
                    email: email,
                    displayName: name
                },
                interfaceConfig: {
                    CONNECTION_DISCONNECTED_URL: '<?php echo $leaveUrl; ?>', 
                },
            });
            // Redirect users to $leaveUrl upon closing the meeting
            api.on('readyToClose', () => {
                window.location.href = '<?php echo $leaveUrl; ?>';
            });
        }
       // var api = new JitsiMeetExternalAPI(domain, options);

       // api.executeCommand('subject', 'Streaming Live');

        // api.on('readyToClose', () => {
        //     window.close();
        // });
         
        
       
    </script>
@endif


<script type="text/javascript">
    //Auto enter the password
    api.on('passwordRequired', function() {
        api.executeCommand('password', join_pass);
    });
</script>










