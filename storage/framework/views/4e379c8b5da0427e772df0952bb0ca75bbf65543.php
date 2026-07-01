   
<?php $__env->startSection('content'); ?>
<div class="row justify-content-center ins-two">
  	<div class="col-md-6">
    	<div class="card">
      		<div class="card-body">
        		<div class="panel panel-default ins-three" data-collapsed="0">
    				<!-- panel body -->
    				<div class="panel-body ins-four">
			            <p class="ins-four">
			              <?php echo e(__('We ran diagnosis on your server.').' '.__('Review the items that have a red mark on it.').' '.__('If everything is green, you
			              are good to go to the next step.')); ?>

			            </p>
		            	<br>
                  <?php if($isLocalInstall): ?>
                    <p class="ins-four">
                      <strong><?php echo e(__('Local development mode')); ?></strong>: <?php echo e(__('file permission checks are skipped for localhost.')); ?>

                    </p>
                  <?php endif; ?>

                  <?php $__currentLoopData = $requirements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requirement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <p class="ins-four">
                      <?php if($requirement['passed']): ?>
                        <i class="fas fafas fa-check ins-nine text-success"></i>
                      <?php else: ?>
                        <i class="fas fa-times ins-ten text-danger"></i>
                      <?php endif; ?>
                      <strong><?php echo e(__($requirement['label'])); ?></strong>: <?php echo e(__($requirement['message'])); ?>

                    </p>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
			            <p class="ins-four">
			              <strong><?php echo e(__('To continue the installation process, all the above requirements are needed to be checked')); ?></strong>
			            </p>
		            	<br>
			            <?php if($valid): ?>
			              <p>
		                  <a href="<?php echo e($nextUrl); ?>" class="btn btn-primary">
		                    <?php echo e(__('Continue')); ?>

		                  </a>
			              </p>
			            <?php endif; ?>

			            <?php if(!$valid): ?>
			              <p>
		                  <a href="<?php echo e($nextUrl); ?>" class="btn btn-primary disabled" aria-disabled="true">
		                    <?php echo e(__('Continue')); ?>

		                  </a>
			                <a href="<?php echo e(route('step1')); ?>" class="btn btn-primary" >
			                  <i class="mdi mdi-refresh"></i><?php echo e(__('Reload')); ?>

			                </a>
			              </p>
			            <?php endif; ?>
    				</div>
    			</div>
      		</div>
    	</div>
  	</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('install.index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/andrejprus/Herd/sociopro/resources/views/install/step1.blade.php ENDPATH**/ ?>