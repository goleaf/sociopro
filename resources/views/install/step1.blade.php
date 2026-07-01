@extends('install.index')
   
@section('content')
<div class="row justify-content-center ins-two">
  	<div class="col-md-6">
    	<div class="card">
      		<div class="card-body">
        		<div class="panel panel-default ins-three" data-collapsed="0">
    				<!-- panel body -->
    				<div class="panel-body ins-four">
			            <p class="ins-four">
			              {{ __('We ran diagnosis on your server.').' '.__('Review the items that have a red mark on it.').' '.__('If everything is green, you
			              are good to go to the next step.') }}
			            </p>
		            	<br>
                  @if($isLocalInstall)
                    <p class="ins-four">
                      <strong>{{ __('Local development mode') }}</strong>: {{ __('file permission checks are skipped for localhost.') }}
                    </p>
                  @endif

                  @foreach($requirements as $requirement)
                    <p class="ins-four">
                      @if($requirement['passed'])
                        <i class="fas fafas fa-check ins-nine text-success"></i>
                      @else
                        <i class="fas fa-times ins-ten text-danger"></i>
                      @endif
                      <strong>{{ __($requirement['label']) }}</strong>: {{ __($requirement['message']) }}
                    </p>
                  @endforeach
			            <p class="ins-four">
			              <strong>{{ __('To continue the installation process, all the above requirements are needed to be checked') }}</strong>
			            </p>
		            	<br>
			            @if($valid)
			              <p>
		                  <a href="{{ $nextUrl }}" class="btn btn-primary">
		                    {{ __('Continue') }}
		                  </a>
			              </p>
			            @endif

			            @if(!$valid)
			              <p>
		                  <a href="{{ $nextUrl }}" class="btn btn-primary disabled" aria-disabled="true">
		                    {{ __('Continue') }}
		                  </a>
			                <a href="{{ route('step1') }}" class="btn btn-primary" >
			                  <i class="mdi mdi-refresh"></i>{{ __('Reload') }}
			                </a>
			              </p>
			            @endif
    				</div>
    			</div>
      		</div>
    	</div>
  	</div>
</div>
@endsection
