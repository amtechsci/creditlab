<?php

function creditlab_lsp_default_partners(): array
{
	return [
		['name' => 'Fairdebt solutions pvt Ltd', 'category' => 'Collection and Recoveries', 'status' => 'Active'],
		['name' => 'Shivtel Communications private limited', 'category' => 'Communication Service - Outbound Dialing', 'status' => 'Active'],
		['name' => 'Synergipro BPO solutions Pvt Ltd', 'category' => 'Collection and Recoveries', 'status' => 'Active'],
		['name' => 'Beyond Financial Services', 'category' => 'Collection and Recoveries', 'status' => 'Active'],
		['name' => 'S V Business Solutions', 'category' => 'Collection and Recoveries', 'status' => 'Active'],
	];
}

function creditlab_ensure_lsp_partners_table(): bool
{
	global $db;
	if (!isset($db) || !@mysqli_ping($db)) {
		return false;
	}

	mysqli_query($db, "CREATE TABLE IF NOT EXISTS `lsp_partners` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`category` varchar(255) NOT NULL,
		`status` varchar(32) NOT NULL DEFAULT 'Active',
		`sort_order` int(11) NOT NULL DEFAULT 0,
		`active` tinyint(1) NOT NULL DEFAULT 1,
		`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$check = mysqli_query($db, 'SELECT id FROM lsp_partners LIMIT 1');
	if ($check && mysqli_num_rows($check) > 0) {
		return true;
	}

	$defaults = creditlab_lsp_default_partners();
	foreach ($defaults as $i => $partner) {
		$name = mysqli_real_escape_string($db, $partner['name']);
		$category = mysqli_real_escape_string($db, $partner['category']);
		$status = mysqli_real_escape_string($db, $partner['status']);
		$sort = (int) ($i + 1);
		mysqli_query($db, "INSERT INTO lsp_partners (name, category, status, sort_order, active)
			VALUES ('$name', '$category', '$status', $sort, 1)");
	}

	return true;
}

function creditlab_get_lsp_partners(bool $public_only = true): array
{
	global $db;
	if (!isset($db) || !@mysqli_ping($db) || !creditlab_ensure_lsp_partners_table()) {
		return creditlab_lsp_default_partners();
	}

	$where = $public_only ? 'WHERE active = 1' : '';
	$result = mysqli_query($db, "SELECT * FROM lsp_partners $where ORDER BY sort_order ASC, id ASC");
	if (!$result) {
		return creditlab_lsp_default_partners();
	}

	$partners = [];
	while ($row = mysqli_fetch_assoc($result)) {
		$partners[] = $row;
	}

	return $partners ?: creditlab_lsp_default_partners();
}

function creditlab_get_lsp_partner(int $id): ?array
{
	global $db;
	if ($id < 1 || !isset($db) || !@mysqli_ping($db) || !creditlab_ensure_lsp_partners_table()) {
		return null;
	}

	$result = mysqli_query($db, "SELECT * FROM lsp_partners WHERE id = $id LIMIT 1");
	if (!$result || mysqli_num_rows($result) === 0) {
		return null;
	}

	return mysqli_fetch_assoc($result);
}

function creditlab_save_lsp_partner(array $input, ?int $id = null): bool
{
	global $db;
	if (!isset($db) || !@mysqli_ping($db) || !creditlab_ensure_lsp_partners_table()) {
		return false;
	}

	$name = mysqli_real_escape_string($db, trim($input['name'] ?? ''));
	$category = mysqli_real_escape_string($db, trim($input['category'] ?? ''));
	$status = mysqli_real_escape_string($db, trim($input['status'] ?? 'Active'));
	$sort_order = (int) ($input['sort_order'] ?? 0);
	$active = !empty($input['active']) ? 1 : 0;

	if ($name === '' || $category === '') {
		return false;
	}

	if ($status === '') {
		$status = 'Active';
	}

	if ($id !== null && $id > 0) {
		$sql = "UPDATE lsp_partners SET
			name = '$name',
			category = '$category',
			status = '$status',
			sort_order = $sort_order,
			active = $active
			WHERE id = $id";
	} else {
		if ($sort_order < 1) {
			$max = mysqli_query($db, 'SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM lsp_partners');
			$row = $max ? mysqli_fetch_assoc($max) : ['max_sort' => 0];
			$sort_order = (int) $row['max_sort'] + 1;
		}
		$sql = "INSERT INTO lsp_partners (name, category, status, sort_order, active)
			VALUES ('$name', '$category', '$status', $sort_order, $active)";
	}

	return (bool) mysqli_query($db, $sql);
}

function creditlab_delete_lsp_partner(int $id): bool
{
	global $db;
	if ($id < 1 || !isset($db) || !@mysqli_ping($db) || !creditlab_ensure_lsp_partners_table()) {
		return false;
	}

	return (bool) mysqli_query($db, "DELETE FROM lsp_partners WHERE id = $id");
}
