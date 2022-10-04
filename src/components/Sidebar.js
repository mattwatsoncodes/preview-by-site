import { Panel, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
// Note that this comes from edit-site for template sidebar.
import { PluginSidebar } from '@wordpress/edit-site';

// PoC, this needs locking down so it only renders for templates, not template parts.
export default function( props ) {

	// Get the post ID.
	const postId = useSelect( ( select ) => {
		const editedPostId = select('core/edit-site').getEditedPostId();
		if ( ! editedPostId ) {
			return null;
		}
		return editedPostId;
	} );
	if ( ! postId ) {
		return '';
	}

	/**
	 * As the description says, in the final version you should be able to choose
	 * the site, the post type and the post for the preview.
	 */
	return (
		<PluginSidebar
			name="preview-by-site"
			title={ __( 'Preview by Site', 'preview-by-site' ) }
		>
			<Panel>
				<PanelBody
					title={ __( 'Preview by Site', 'preview-by-site' ) }
					icon=""
				>
					<p>This is a PoC. You should be able to choose the site, post type and post here.</p>
					<a href={ templateSidebarSettings.previewLink + '&preview_template=' + postId } target="_blank" class="button button-primary">Preview</a>
				</PanelBody>
			</Panel>
		</PluginSidebar>
	);
}