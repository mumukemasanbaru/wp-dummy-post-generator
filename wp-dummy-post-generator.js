/**
 * WordPress Dummy Post Generator
 * @url https://github.com/rogerhub/wp-dummy-post-generator
 * @license GPLv2
 */
var wp_dummy_post_generator_category_count = -1;
var wp_dummy_post_generator_num = 10;
jQuery(document).ready(function() {
	jQuery('#wp_dummy_post_generator_ajax_insert_post ' + 'button').click(function(event) {
		event.preventDefault();
		jQuery(this).attr('disabled', 'disabled').text('Working..');
		if (wp_dummy_post_generator_category_count != -1) {
			// Already started
			console.log("Blocked attempt to restart post creation.");
		} else {
			wp_dummy_post_generator_category_count = wp_dummy_post_generator_categories.length;
			wp_dummy_post_generator_insert_post(
				jQuery('#wp_dummy_post_generator_ajax_insert_post ' + 'input[name=wp_dummy_post_generator_num_posts]').val(), // num_posts
				(jQuery('#wp_dummy_post_generator_ajax_insert_post ' + 'input[name=wp_dummy_post_generator_leaf_only]').attr('checked') == 'checked') ? '1' : '0', // leaf_only
				(jQuery('#wp_dummy_post_generator_ajax_insert_post ' + 'input[name=wp_dummy_post_generator_random_author]').attr('checked') == 'checked') ? '1' : '0' // random_author
			);
		}
	});
	jQuery('#wp_dummy_post_generator_js_export ' + 'button').click(function(event) {
		event.preventDefault();
		window.open('data:text/csv;charset=utf-8,' + escape(exportJSON));
	});
});

function wp_dummy_post_generator_insert_post(num_posts, leaf_only, random_author) {
	if (wp_dummy_post_generator_categories == undefined || wp_dummy_post_generator_categories.length == 0) {
		jQuery('#wp_dummy_post_generator_ajax_response').html('Finished.');
		jQuery('form[name=wp_dummy_post_generator_form]').submit();
		return;
	}
	var cat_id = wp_dummy_post_generator_categories.splice(0, wp_dummy_post_generator_num).join(',');
	jQuery('#wp_dummy_post_generator_ajax_response').html('Processing category ' + (wp_dummy_post_generator_category_count - wp_dummy_post_generator_categories.length) + ' of ' + wp_dummy_post_generator_category_count);

	jQuery.post(ajaxurl, {
		action: 'wp_dummy_post_generator_ajax',
		security: wp_dummy_post_generator_ajax_nonce,
		num_posts: num_posts,
		leaf_only: leaf_only,
		random_author: random_author,
		cat_id: cat_id
	}, (function(num_posts, leaf_only, random_author) {
			return (function(response) {
				if (response == '') {
					wp_dummy_post_generator_insert_post(num_posts, leaf_only, random_author);
				} else {
					jQuery('#wp_dummy_post_generator_ajax_response').html('Error: ' + response);
				}
			});
		})(num_posts, leaf_only, random_author)
	);
}
