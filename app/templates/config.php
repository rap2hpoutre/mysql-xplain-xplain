<?php $this->layout('layout'); ?>
<div class="container">
	<?php if (isset($this->error)): ?>
		<div class="alert alert-danger"><?=$this->error?></div>
	<?php endif; ?>
	<form role="form" method="post">
		<div class="form-group">
			<label for="host">Host</label>
			<input type="text" class="form-control" name="host" id="host" placeholder="localhost" value="<?php if (isset($_SESSION['mysql']['host'])) echo $_SESSION['mysql']['host']; ?>">
		</div>
		<div class="form-group">
			<label for="user">User</label>
			<input type="text" class="form-control" name="user" id="user" placeholder="root" value="<?php if (isset($_SESSION['mysql']['user'])) echo $_SESSION['mysql']['user']; ?>">
		</div>
		<div class="form-group">
			<label for="password">Password</label>
			<input type="password" class="form-control" name="password" id="password" value="<?php if (isset($_SESSION['mysql']['password'])) echo $_SESSION['mysql']['password']; ?>">
		</div>
		<div class="form-group">
			<label for="base">Default database</label>
			<input type="text" class="form-control" name="base" id="base" value="<?php if (isset($_SESSION['mysql']['base'])) echo $_SESSION['mysql']['base']; ?>">
		</div>
		<div class="checkbox">
			<label>
				<input name="permanent_login" type="checkbox"> Permanent login
			</label>
		</div>
		<button type="submit" class="btn btn-default">Submit</button>
	</form>
</div>