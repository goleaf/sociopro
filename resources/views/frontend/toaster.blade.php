<script type="text/javascript">
	"use strict";

	function alert_message(message){
	    $.toast({
	        content: message,
	        position: "bottom-left"
	    })
	}
</script>

@if($successMessage)
	<script>
		"use strict";

		alert_message("{{ $successMessage }}");
	</script>
@endif

@if($infoMessage)
	<script>
		"use strict";

		alert_message("{{ $infoMessage }}");
	</script>
@endif

@if($errorMessage)
	<script>
		"use strict";

		alert_message("{{ $errorMessage }}");
	</script>
@endif
