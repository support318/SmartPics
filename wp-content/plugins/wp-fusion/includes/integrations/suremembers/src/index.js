import WpfSelect from '@verygoodplugins/wpfselect';
import { useState } from '@wordpress/element';
import { link } from '@wordpress/icons';

const { addFilter } = wp.hooks;
const { __ } = wp.i18n;

const formatTags = ( tags ) => {
	if ( ! tags ) {
		return [];
	}
	if ( Array.isArray( tags ) ) {
		return tags;
	}
	if ( typeof tags === 'object' ) {
		return [ tags ];
	}

	return [];
};

addFilter( 'suremembers_sidebar_metaboxes_after', 'wpfusion', function () {
	const [ applyTags, setApplyTags ] = useState(
		formatTags( window.wpf_suremembers.apply_tags )
	);
	const [ linkTags, setLinkTags ] = useState(
		formatTags( window.wpf_suremembers.tag_link )
	);
	const [ staticApplyTags, setStaticApplyTags ] = useState(
		window.wpf_suremembers.raw_apply_tags || ''
	);
	const [ staticLinkTags, setStaticLinkTags ] = useState(
		window.wpf_suremembers.raw_tag_link || ''
	);

	const applyTagsString = window.wpf_suremembers.apply_tags_string;
	const tagLinkString = window.wpf_suremembers.tag_link_string;

	const onChange = ( holder, value, single = false ) => {
		let string = '';

		if ( ! value ) {
			return string; // Return empty string when value is cleared
		}

		if ( single ) {
			string = value.value + ',';
		} else {
			value.forEach( ( tag ) => {
				string = string + tag.value + ',';
			} );
		}

		return string;
	};

	return (
		<div>
			<div
				className="bg-white rounded-sm divide-y-[1px] w-full"
				id="wpf_meta_box_suremembers"
			>
				<div
					className="px-8 py-5 border-solid  border-bottom-gray-500 font-medium text-[15px]"
					id="wpf_meta_box_suremembers_title"
				>
					{ __( 'WP Fusion', 'wp-fusion' ) }
				</div>
				<div
					className="px-8 py-6 text-sm"
					id="wpf_meta_box_suremembers_content"
				>
					<div className="flex flex-col space-y-4">
						<input
							type="hidden"
							name="wpf_meta_box_suremembers_nonce"
							value={ window.wpf_suremembers.nonce }
						/>
						<input
							type="hidden"
							id="wpf-suremembers-apply-tags"
							name="wp_fusion[apply_tags]"
							value={ staticApplyTags }
						/>
						<sc-form-control
							className="hydrated"
							id="tag_apply"
							label={ window.wpf_admin.strings.applyTags }
							size="medium"
						>
							<WpfSelect
								existingTags={ applyTags }
								onChange={ ( value ) => {
									const holder = document.getElementById(
										'wpf-suremembers-apply-tags'
									);

									const processedValue = onChange(
										holder,
										value
									);

									setStaticApplyTags( processedValue );
									setApplyTags( value );
								} }
								elementID="wpf-sure-members-tags"
							/>
						</sc-form-control>
						<span className="description">{ applyTagsString }</span>
					</div>
					<div className="flex flex-col space-y-4">
						<input
							type="hidden"
							id="wpf-suremembers-link-tags"
							name="wp_fusion[tag_link]"
							value={ staticLinkTags }
						/>
						<sc-form-control
							className="hydrated"
							id="tag_link"
							label={ window.wpf_admin.strings.linkWithTag }
							size="medium"
						>
							<WpfSelect
								existingTags={ linkTags }
								onChange={ ( value ) => {
									const holder = document.getElementById(
										'wpf-suremembers-link-tags'
									);
									const processedValue = onChange(
										holder,
										value,
										true
									);

									setStaticLinkTags( processedValue );
									setLinkTags( value || null );
								} }
								elementID="wpf-sure-members-line"
								isMulti={ false }
								isClearable={ true }
								sideIcon={ link }
							/>
						</sc-form-control>
						<span className="description">{ tagLinkString }</span>
					</div>
				</div>
			</div>
		</div>
	);
} );
