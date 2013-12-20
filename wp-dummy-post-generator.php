<?php
/**
 * Plugin Name: WordPress Dummy Post Generator
 * Plugin URI: https://www.github.com/rogerhub/wp-dummy-post-generator
 * Description: This WordPress plugin generates dummy posts. It can also be configured to create categories, clear the database, and set settings.
 * Version: 0.1
 * Author: Roger Chen
 * Author URI: http://rogerhub.com
 * License: GPL2
 */

/**
 * Class for WP Dummy Post Generator
 */
class WP_Dummy_Post_Generator {
	protected $prefix = 'wp_dummy_post_generator_';

	/**
	 * Initializes WP_Dummy_Post_Generator
	 */
	public function __construct() {
		add_action('admin_menu', array(&$this, 'admin_register_submenu'));
		add_action("wp_ajax_{$this->prefix}ajax", array(&$this, 'insert_post_ajax'));
	}
	/**
	 * Adds the submenu for WP_Dummy_Post_Generator under Settings
	 */
	public function admin_register_submenu() {
		$hook = add_submenu_page('tools.php', 'WP Dummy Post Generator', 'Dummy Posts', 'manage_options', "{$this->prefix}menu.php", array(&$this, 'admin_panel'));
		add_action("admin_print_scripts-$hook", array(&$this, 'assets'));
	}
	/**
	 * Adds the wp-dummy-post-generator.js script to the head of the Dummy Post Generator panel
	 */
	public function assets($hook) {
		wp_register_script($this->prefix.'js', plugins_url('wp-dummy-post-generator.js', __FILE__), array('jquery'));
		wp_enqueue_script($this->prefix.'js');
	}
	/**
	 * Shows the Dummy Post Generator Panel
	 */
	public function admin_panel() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		if (isset($_POST['_wpnonce'])) {
			if (!check_admin_referer($this->prefix."form")) { ?>
				<div class="error"><p>Your session has timed out. Please try again.</p></div><?php
			} else {
				$operation = $this->process_action(@$_POST[$this->prefix."action"]);
				if (is_wp_error($operation)) { ?>
					<div class="error"><p><strong>An error occurred: <?php echo $operation->get_error_message(); ?></strong></p></div><?php
				} else { ?>
					<div class="updated"><p><strong><?php echo (is_string($operation) ? $operation : 'Finished.'); ?></strong></p></div><?php
				}
			}
		}
		$categories = get_all_category_ids();
		$category_count = count($categories);
		if ($this->is_dirty()) {
			$category_count = 'Unavailable (<a href="">please refresh</a>)';
		}

		$setting = $this->load_setting();

		?>		
		<div class="wrap"><div id="icon-tools" class="icon32"><br /></div>
		<h2>Dummy Post Generator</h2>
		<p>The tools on this page can be used to generate dummy data for WordPress.</p>
		<form method="post" name="<?php echo $this->prefix."form"; ?>" enctype="multipart/form-data">
		<?php wp_nonce_field("{$this->prefix}form"); ?>
		<h3>Import/Export Settings</h2>
		<p>You can specify settings (a list of categories, site name, etc.) below that can be exported and imported via this plugin.</p>
		<table class="form-table">
		<tr valign="top">
			<th scope="row">Import Settings</th>
			<td>
			<fieldset><input type="file" name="<?php echo $this->prefix . "import_file"; ?>" /> <button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" value="import_setting">Import settings file</button>
			</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Export all settings</th>
			<td>
			<fieldset id="<?php echo $this->prefix."js_export"; ?>"><button type="submit" class="button-primary button" name="<?php echo $this->prefix."action"; ?>" value="export_setting">Download export file</button>
			<script type="text/javascript">
			var exportJSON = "<?php echo addslashes($this->export()); ?>";
			</script>
			</fieldset>
			</td>
		</tr>
		</table>
		<h3>Create Dummy Data</h3>
		<table class="form-table">
		<tr valign="top">
			<th scope="row">Build Categories<br /><br />Example usage:<div style="font-family: monospace; margin: 0.5em 1em;">Parent 1<br />&nbsp;Child 1<br />&nbsp;Child 2<br />&nbsp;Child 3//custom-slug<br />Parent 2<br />Parent 3</div></th>
			<td>
			<fieldset>Current number of categories: <?php echo $category_count; ?><br />
			<textarea name="<?php echo $this->prefix."categories"; ?>" style="font-family: monospace; width: 100%; height: 240px;"><?php echo esc_textarea($setting['categories']); ?></textarea><br />
				<button type="submit" class="button-primary button" name="<?php echo $this->prefix."action"; ?>" value="build_category">Build Categories</button> <button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" value="save_category">Save Categories</button>
			</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Generate Dummy Posts</th>
			<td>
			<fieldset id="<?php echo $this->prefix."ajax_insert_post"; ?>">
				<label for="<?php echo $this->prefix."num_posts"; ?>">
				Generate <input name="<?php echo $this->prefix."num_posts"; ?>" type="number" min="0" step="1" value="<?php echo esc_attr($setting['num_posts']); ?>" class="small-text" /> posts per category. (Recommended 1 at a time.)
				</label><br />
				<label for="<?php echo $this->prefix."leaf_only"; ?>">
				<input type="checkbox" name="<?php echo $this->prefix."leaf_only"; ?>" value="1" <?php if ($setting['leaf_only'] == '1') { ?>checked="checked"<?php } ?> /> Leaf categories (no children) only.
				</label><br />
				<label for="<?php echo $this->prefix."random_author"; ?>">
				<input type="checkbox" name="<?php echo $this->prefix."random_author"; ?>" value="1" <?php if ($setting['random_author'] == '1') { ?>checked="checked"<?php } ?> /> Random author for each post.
				</label><br />
				<?php if ($this->is_dirty()) { ?>
				<button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" disabled="disabled" value="generate_post">Generate Posts (unavailable, please refresh page)</button>
				<?php } else { $ajax_nonce = wp_create_nonce($this->prefix.'ajax'); ?>
				<script type="text/javascript"><?php echo "var {$this->prefix}categories = [".implode(', ', $categories)."];"; ?></script>
				<script type="text/javascript"><?php echo "var {$this->prefix}ajax_nonce = '{$ajax_nonce}';"; ?></script>
				<button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" value="generate_post">Generate Posts</button> <span id="<?php echo $this->prefix."ajax_response"; ?>"></span>
				<?php } ?>
			</fieldset>
			</td>
		</tr>
		</table>
		<h3>Delete Existing Data</h3>
		<table class="form-table">
		<tr valign="top">
			<th scope="row">Delete Existing Posts<br />(Limit: 500/request.)</th>
			<td>
			<fieldset>
				<button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" value="delete_post">Delete All Existing Posts</button>
			</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Delete Existing Categories</th>
			<td>
			<fieldset>
				<button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" value="delete_category">Delete All Existing Categories</button>
			</fieldset>
			</td>
		</tr>
		</table>
		<h3>Apply Settings</h3>
		<table class="form-table">
		<tr valign="top">
			<th scope="row">Available Settings</th>
			<td>
				<fieldset>
					<label for="<?php echo $this->prefix."sitename"; ?>">
					<input type="text" name="<?php echo $this->prefix."sitename"; ?>" value="<?php echo $setting['sitename']; ?>" /> Site Title
					</label>
					<br />
					<label for="<?php echo $this->prefix."tagline"; ?>">
					<input type="text" name="<?php echo $this->prefix."tagline"; ?>" value="<?php echo $setting['tagline']; ?>" /> Tagline
					</label>
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Update Settings</th>
			<td>
				<fieldset>
					<button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" value="set_setting">Apply these settings</button>
				</fieldset>
			</td>
		</tr>
		</table>
		<h3>Save Settings</h3>
		<table class="form-table">
		<tr valign="top">
			<th scope="row">Save settings without applying them</th>
			<td>
				<fieldset>
					<button type="submit" class="button-primary button" name="<?php echo $this->prefix."action"; ?>" value="save_setting">Save all</button> <button type="submit" class="button" name="<?php echo $this->prefix."action"; ?>" value="clear_setting">Reset to default</button>
				</fieldset>
			</td>
		</tr>
		</table>
		</form>
		<?php
	}
	/**
	 * Helper function that determines if db cache is dirty
	 */
	public function is_dirty() {
		$action = @$_POST[$this->prefix.'action'];
		return ($action == 'build_category' || $action == 'delete_category');
	}
	/**
	 * Processes the action decided by the form
	 * 
	 * @param $action is the thing to do (set_setting, delete_post, etc.)
	 */
	protected function process_action($action) {
		switch ($action) {
		case "build_category":
			$category_count = count(get_all_category_ids());
			if ($category_count > 1) {
				return new WP_Error('illegal', __("Please clear out the existing categories before inserting the new ones."));
			}			
			return $this->insert_category($_POST[$this->prefix . 'categories']);
		case "delete_category":
			$this->delete_category();
			return true;
		case "delete_post":
			$this->delete_post();
			return true;
		case "import_setting":
			return $this->import_setting($_FILES[$this->prefix . 'import_file']['tmp_name']);
		case "set_setting":
			return $this->set_setting();
		case "save_category": // Placebo, hehe
		case "save_setting":
			$this->save_setting();
			return "Saved.";
		case "clear_setting":
			$this->clear_setting();
			return true;
		case "export_setting":
		case "generate_post":
			return new WP_Error('nojavascript', __("Post generation requires JavaScript. Please make sure it is enabled or check the console for errors."));
		default:
			return true;
		}
	}
	/**
	 * Renders exported JSON settings.
	 */
	protected function export() {
		$setting = $this->load_setting();
		return json_encode($setting, JSON_FORCE_OBJECT);
	}
	/**
	 * Updates site blogname and tagline
	 */
	protected function set_setting() {
		if (!empty($_POST[$this->prefix . 'sitename'])) {
			update_option('blogname', $_POST[$this->prefix . 'sitename']);
		}
		if (!empty($_POST[$this->prefix . 'tagline'])) {
			update_option('blogdescription', $_POST[$this->prefix . 'tagline']);
		}
		return "Finished. Refresh the page to see changes.";
	}
	/**
	 * Loads Settings stored in a file
	 *
	 * @arg $filepath is the path 
	 */
	protected function import_setting($filepath) {
		$setting = file_get_contents($filepath);
		if (false === $setting) {
			return new WP_Error('invalidupload', __('It looks like the file upload failed. Please try again.'));
		}
		$setting = json_decode($setting, true);
		return update_option($this->prefix . "options", $setting);
	}
	/**
	 * Saves Settings
	 */
	protected function save_setting() {
		$setting = array();
		$whitelist_fields = array('categories', 'num_posts', 'leaf_only', 'random_author', 'sitename', 'tagline');
		foreach ($whitelist_fields as $whitelist_field) {
			if (array_key_exists($this->prefix . $whitelist_field, $_POST)) {
				$setting[$whitelist_field] = $_POST[$this->prefix . $whitelist_field];
			}
		}
		return update_option($this->prefix . "options", $setting);
	}
	/**
	 * Resets all settings to default
	 */
	protected function clear_setting() {
		return delete_option($this->prefix."options");	
	}
	/**
	 * Loads saved settings or returns default set
	 */
	protected function load_setting() {
		return get_option($this->prefix."options", array(
			'categories' => "Parent Category\n Child Category\n Another Child\n  Grandchild Category\n Yet Another Child//custom-slug-for-category\nParent Category//custom-slug-avoids-name-collision",
			'num_posts' => 1,
			'leaf_only' => '1',
			'random_author' => '1',
			'sitename' => 'My WordPress Blog',
			'tagline' => 'Just another WordPress site',
		));
	}

	/**
	 * Generates Categories
	 *
	 * @param $cats is the list of categories to be inserted
	 */
	protected function insert_category($cats) {
		$cats = explode("\n", $cats);
		$parents = array(0); // FILO Stack
		foreach ($cats as $cat) {
			$tier = $this->spaces($cat);
			if (trim($cat) == '') {
				continue;
			} else if ($tier + 1 < count($parents)) {
				array_splice($parents, $tier + 1, count($parents));
			}
			$cat_ID = wp_insert_term($this->catname($cat), 'category', array(
				'slug' => $this->slug($cat),
				'parent' => $parents[count($parents) - 1],
			));
			if (is_wp_error($cat_ID)) {
				return $cat_ID;
			} else {
				$parents[] = $cat_ID['term_id'];
			}
		}
		return true;
	}
	/**
	 * Helper function that counts spaces at the beginning of a string
	 *
	 * @param $str is the string to be parsed
	 */
	protected function spaces($str) {
		if (trim($str) == "") {
			return -1;
		}
		$count = 0;
		while (strlen($str) > 0 && substr($str, 0, 1) == ' ') {
			$str = substr($str, 1);
			$count++;
		}
		return $count;
	}
	/**
	 * Helper function that parses the slug of a category
	 *
	 * @param $str is the category str
	 */
	protected function slug($str) {
		if (strpos($str, '//') !== false) {
			return substr($str, strpos($str, '//') + 2);
		}
		return sanitize_title($str);
	}
	/**
	 * Helper function that parses the name of a category
	 *
	 * @param $str is the category str
	 */
	protected function catname($str) {
		if (strpos($str, '//') === false) {
			return $str;
		}
		return substr($str, 0, strpos($str, '//'));
	}
	/**
	 * Helper function that inserts posts into batches of categories
	 */
	public function insert_post_ajax() {
		check_ajax_referer($this->prefix.'ajax', 'security');
		if (!current_user_can('manage_options')) {
			die('Forbidden operation.');
		}

		// Read parameters
		$n = $_POST['num_posts'];
		$leaf_only = ($_POST['leaf_only'] == '1');
		$random_author = ($_POST['random_author'] == '1');
		$categories = explode(',', $_POST['cat_id']);

		if ($random_author) {
			$authors = array_map(function($author) {
				return $author->ID;
			}, get_users(array('number' => 9999)));
		} else {
			$authors = array(get_current_user_id());
		}

		foreach ($categories as $category) {
			if (!$leaf_only || $this->is_leaf($category)) {
				for ($i = 0; $i < $n; $i++) {
					$post_title = str_replace('.', '', $this->filler_text(1)); // Grab 1 sentence, and remove period
					$opts = array(
						'post_author' => $authors[array_rand($authors)],
						'post_content' => $this->filler_text(5500),
						'post_date' => $this->recent_date(),
						'post_name' => sanitize_title($post_title),
						'post_status' => 'draft',
						'post_title' => $post_title,
						'post_type' => 'post',
					);
					$op = wp_insert_post($opts, true);
					if (is_wp_error($op)) {
						die($op->get_error_message());
					} else {
						wp_publish_post($op);
						wp_set_post_terms($op, array($category), 'category');
					}
				}
			}
		}

		die();
	}
	/**
	 * Helper function that returns whether a category is a leaf
	 *
	 * Leaf categories are categories that have no children. It's
	 * typical practice to put all posts in leaf categories.
	 *
	 * @param $cat_ID is the category's ID
	 */
	protected function is_leaf($cat_ID) {
		return (count(get_categories(array('parent' => $cat_ID, 'number' => 1, 'hide_empty' => 0))) == 0);
	}
	/**
	 * Helper function that returns a recent date
	 */
	protected function recent_date() {
		return date('Y-m-d H:i:s', time() - rand(0, 30*24*60*60)); // Last 30 days
	}
	/**
	 * Helper function that generates filler text
	 *
	 * @param $length is target length in characters
	 */
	protected function filler_text($length) {
		$lipsum = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam at eros sem. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae. Duis tempus, eros eget semper varius, orci purus consectetur magna, id aliquam tortor tellus in turpis. Nullam sed nunc in libero laoreet euismod molestie sed nulla. Vivamus ac egestas elit. Curabitur sagittis lectus vitae orci consectetur porta. Nullam at arcu diam. Cras mattis ultrices convallis. In vitae molestie justo. Vivamus dolor lacus, ullamcorper vel imperdiet nec, accumsan id est. Aenean lobortis imperdiet mauris id convallis. Phasellus fringilla euismod lorem in convallis. Donec ornare aliquet neque vel congue. Praesent lobortis, elit ut eleifend feugiat, tellus nisl blandit arcu, eget elementum odio ante nec est. Pellentesque facilisis, nunc sit amet auctor ultrices, sapien urna vulputate nisi, sit amet auctor enim nisl eu odio. Maecenas in mi eget sapien ultrices ullamcorper. Nulla facilisi. Etiam scelerisque, diam nec consequat facilisis, mauris diam gravida nunc, nec commodo tellus purus nec diam. Fusce et turpis est. Nulla facilisi. Vivamus fringilla laoreet urna. Proin vitae nulla dolor, id tempor dui. Maecenas quam enim, sollicitudin eu varius eget, semper ac elit. Aliquam erat volutpat. Phasellus et arcu non arcu consectetur malesuada. Donec nec felis mauris, nec mattis neque. Nulla pellentesque commodo magna sit amet pretium. Curabitur varius cursus nunc ac vehicula. Duis vitae orci sem, at elementum massa. Sed a tincidunt nulla. Nunc in neque sapien. Duis suscipit consectetur pharetra. Sed commodo tristique tempor. Sed nulla lorem, molestie in scelerisque non, pharetra vel est. Nam cursus porttitor dui quis facilisis. Vivamus interdum rhoncus velit, condimentum placerat lacus rhoncus ac. Pellentesque a enim tellus. Vestibulum ullamcorper orci et lacus ultrices non ultricies tortor dignissim. Nulla facilisi. Aenean nisi diam, pellentesque sed vulputate eu, adipiscing a risus. Morbi turpis sem, viverra sed tincidunt dapibus, adipiscing in justo. Curabitur eu tortor sed tortor tristique convallis. Fusce porta justo condimentum est suscipit in lobortis neque ultricies. Nam condimentum eros vel dolor eleifend eleifend. Integer sit amet ipsum odio, eget sodales neque. Sed vel orci quis dui faucibus bibendum. Sed dictum sagittis massa ut venenatis. Aliquam eleifend orci nec urna tincidunt adipiscing. Nulla massa purus, bibendum ut pulvinar quis, cursus cursus ligula. Vestibulum mattis ligula in est viverra eget tempus massa congue. Vivamus mattis sagittis eros, aliquam pellentesque magna pharetra id. Sed in ipsum in felis commodo pellentesque sed vel sem. Etiam sit amet libero felis, at imperdiet sapien. Nam ac enim augue. Fusce molestie eros risus. Vivamus interdum lorem eros.";
		$lipsum = explode('. ', $lipsum);
		$text = '';
		$breaks = rand(250, 800);
		for (;;) {
			$sentence = $lipsum[array_rand($lipsum)].'.';
			$text .= $sentence;
			$breaks -= strlen($sentence);
			if (strlen($text) > $length) {
				break;
			} else if ($breaks < 0) {
				// Will never insert a paragraph break at the end of the text
				$text .= '\n\n';
				$breaks += rand(250, 800);
			}
		}
		if ($length == 1) {
			return implode(' ', array_slice(explode(' ', $text), 0, rand(6, 15)));
		}
		return $text;
	}
	/**
	 * Removes all the categories
	 */
	protected function delete_category() {
		$categories = get_all_category_ids();
		foreach ($categories as $category) {
			// Attempt to delete category. If category is default "Uncategorized", then delete attempt should silently fail
			wp_delete_category($category);
		}
	}
	/**
	 * Removes all the posts
	 */
	protected function delete_post() {
		$posts = get_posts(array(
			'posts_per_page' => 500, // Will process no more than 500 posts at one time
			'offset' => 0,
		));
		foreach ($posts as $post) {
			wp_delete_post($post->ID, true);
		}
	}
}

if (is_admin()) {
	new WP_Dummy_Post_Generator();
}
