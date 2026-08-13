<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/lsp_partners.php';

$message = '';
$message_type = 'info';
$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit_partner = $edit_id > 0 ? creditlab_get_lsp_partner($edit_id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['save_partner'])) {
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		$ok = creditlab_save_lsp_partner([
			'name' => $_POST['name'] ?? '',
			'category' => $_POST['category'] ?? '',
			'status' => $_POST['status'] ?? 'Active',
			'sort_order' => $_POST['sort_order'] ?? 0,
			'active' => isset($_POST['active']) ? 1 : 0,
		], $id > 0 ? $id : null);

		if ($ok) {
			$message = $id > 0 ? 'Partner updated successfully.' : 'Partner added successfully.';
			$message_type = 'success';
			$edit_id = 0;
			$edit_partner = null;
		} else {
			$message = 'Could not save partner. Please fill all required fields.';
			$message_type = 'danger';
		}
	}

	if (isset($_POST['delete_partner'])) {
		$id = (int) ($_POST['id'] ?? 0);
		if ($id > 0 && creditlab_delete_lsp_partner($id)) {
			$message = 'Partner deleted successfully.';
			$message_type = 'success';
			if ($edit_id === $id) {
				$edit_id = 0;
				$edit_partner = null;
			}
		} else {
			$message = 'Could not delete partner.';
			$message_type = 'danger';
		}
	}
}

$partners = creditlab_get_lsp_partners(false);
?>
<body>
<?php include_once 'Left_menu.php'; include_once 'welcome.php'; include_once 'm_menu.php'; ?>
<div class="container-fluid" style="padding:30px;">
	<h2>LSP Partners</h2>
	<p class="text-muted">Manage the partner list shown on <a href="/lsp.php" target="_blank">creditlab.in/lsp.php</a>.</p>

	<?php if ($message) { ?>
	<div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
	<?php } ?>

	<div class="row">
		<div class="col-md-6">
			<h4><?= $edit_partner ? 'Edit partner' : 'Add partner' ?></h4>
			<form method="post" class="form-horizontal">
				<?php if ($edit_partner) { ?>
				<input type="hidden" name="id" value="<?= (int) $edit_partner['id'] ?>">
				<?php } ?>
				<div class="form-group">
					<label>Name of Partner</label>
					<input type="text" name="name" class="form-control" required
						value="<?= htmlspecialchars($edit_partner['name'] ?? '') ?>">
				</div>
				<div class="form-group">
					<label>Category / Activities</label>
					<input type="text" name="category" class="form-control" required
						value="<?= htmlspecialchars($edit_partner['category'] ?? '') ?>">
				</div>
				<div class="form-group">
					<label>Status</label>
					<select name="status" class="form-control">
						<?php
						$statuses = ['Active', 'Inactive'];
						$current_status = $edit_partner['status'] ?? 'Active';
						foreach ($statuses as $status) {
							$selected = $current_status === $status ? 'selected' : '';
							echo '<option value="' . htmlspecialchars($status) . '" ' . $selected . '>' . htmlspecialchars($status) . '</option>';
						}
						?>
					</select>
				</div>
				<div class="form-group">
					<label>Sort order</label>
					<input type="number" name="sort_order" class="form-control" min="1"
						value="<?= (int) ($edit_partner['sort_order'] ?? (count($partners) + 1)) ?>">
				</div>
				<div class="checkbox">
					<label>
						<input type="checkbox" name="active" value="1"
							<?= !isset($edit_partner['active']) || (int) $edit_partner['active'] === 1 ? 'checked' : '' ?>>
						Show on public page
					</label>
				</div>
				<button type="submit" name="save_partner" class="btn btn-success" style="margin-top:10px;">
					<?= $edit_partner ? 'Update partner' : 'Add partner' ?>
				</button>
				<?php if ($edit_partner) { ?>
				<a href="lsp_partners.php" class="btn btn-default" style="margin-top:10px;">Cancel edit</a>
				<?php } ?>
			</form>
		</div>
	</div>

	<h4 style="margin-top:40px;">All partners</h4>
	<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>S.no</th>
				<th>Name</th>
				<th>Category</th>
				<th>Status</th>
				<th>Sort</th>
				<th>Visible</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php if (!$partners) { ?>
			<tr><td colspan="7">No partners added yet.</td></tr>
			<?php } ?>
			<?php foreach ($partners as $i => $partner) { ?>
			<tr>
				<td><?= $i + 1 ?></td>
				<td><?= htmlspecialchars($partner['name']) ?></td>
				<td><?= htmlspecialchars($partner['category']) ?></td>
				<td><?= htmlspecialchars($partner['status']) ?></td>
				<td><?= (int) $partner['sort_order'] ?></td>
				<td><?= (int) $partner['active'] === 1 ? 'Yes' : 'No' ?></td>
				<td>
					<a href="lsp_partners.php?edit=<?= (int) $partner['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
					<form method="post" style="display:inline;" onsubmit="return confirm('Delete this partner?');">
						<input type="hidden" name="id" value="<?= (int) $partner['id'] ?>">
						<button type="submit" name="delete_partner" class="btn btn-danger btn-sm">Delete</button>
					</form>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php include_once 'foot.php'; ?>
</body>
</html>
