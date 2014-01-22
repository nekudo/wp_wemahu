<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */

if(!class_exists('WP_List_Table'))
{
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WemahuRulesetsTable extends WP_List_Table
{
	public function __construct()
	{
		global $status, $page;

		//Set parent defaults
		parent::__construct(array(
			'singular' => 'ruleset',
			'plural' => 'rulesets',
			'ajax' => false,
		));
	}

	public function column_default($item, $column_name)
	{
		switch($column_name)
		{
			case 'rating':
			case 'director':
				return $item[$column_name];
			break;

			default:
				return print_r($item,true); //Show the whole array for troubleshooting purposes
			break;
		}
	}

	public function column_title($item)
	{
		//Build row actions
		$actions = array(
			'edit' => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
			'delete' => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id'], wp_create_nonce('delete_' . $item['id'])),
		);

		//Return the title contents
		return sprintf('<strong><a class="row-title" href="?page=%3$s&action=edit&id=%2$s">%1$s</a></strong> %4$s',
			$item['title'],
			$item['id'],
			$_REQUEST['page'],
			$this->row_actions($actions)
		);
	}

	public function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item['id']
		);
	}

	public function get_columns(){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
		);
		return $columns;
	}

	public function get_sortable_columns()
	{
		$sortable_columns = array(
			'title' => array('title',false), //true means it's already sorted
		);
		return $sortable_columns;
	}

	public function get_bulk_actions()
	{
		$actions = array(
			'delete' => 'Delete'
		);
		return $actions;
	}

	/**
	 * @global wpdb $wpdb
	 */
	public function prepare_items()
	{
		global $wpdb;

		$per_page = 10;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		/**
		 * Fetch rulesets from database.
		 */
		$data = $wpdb->get_results("SELECT id,name AS title FROM " . $wpdb->rulesets, ARRAY_A);

		/**
		 * Sort data.
		 */
		usort($data, function($a,$b)
		{
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title';
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';
			$result = strcmp($a[$orderby], $b[$orderby]);
			return ($order==='asc') ? $result : -$result;
		});

		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data, (($current_page-1) * $per_page), $per_page);
		$this->items = $data;

		/**
		 * Pagination
		 */
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));
	}
}

$RulesetsTable = new WemahuRulesetsTable();
$RulesetsTable->prepare_items();
?>

<div class="wrap">
	<h2>Rulesets <a href="?page=<?php echo $_REQUEST['page'] ?>&action=add" class="add-new-h2">Add New</a></h2>
	<?php if(!empty($message)): ?>
		<?php $msgClass = ($message['type'] === 'error') ? 'error' : 'updated'; ?>
		<div id="system-message" class="<?php echo $msgClass; ?>">
			<p><strong><?php echo esc_html($message['text']); ?></strong></p>
		</div>
	<?php endif; ?>
	<form id="wemahu-rulesets" method="get">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		<?php $RulesetsTable->display(); ?>
	</form>
</div>