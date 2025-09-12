<?php
/**
 * Email Footer
 *
 * @package AffiliateWP/Templates/Emails
 * @version 1.6
 */

// phpcs:disable Generic.ControlStructures.InlineControlStructure.NotAllowed -- It's okay here.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Legacy code.
// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket -- Legacy code.
// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket -- Legacy code.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Legacy code.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Legacy code.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterOpen -- Formatting OK here.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeEnd -- Formatting OK here.

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline.
$template_footer = '
	border-top:0;
	-webkit-border-radius:3px;
';

$credit = "
	border:0;
	color: #000000;
	font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
	font-size:12px;
	line-height:125%;
	text-align:center;
";

?>
																												</div>
																											</td>
																										</tr>
																								</table>
																								<!-- End Content -->
																						</td>
																				</tr>
																		</table>
																		<!-- End Body -->
																</td>
														</tr>
												</table>

												<p style="text-align: center; margin-top: 20px; font-size: 15px; color: #a09e9e">
													<small>
														<?php echo wp_kses_post( sprintf(
															// Translators: %s is the link to the admin page where the setting is.
															__( 'This email was auto-generated and sent from <a href="%1$s" style="color: #a09e9e;">%2$s</a>.', 'affiliate-wp' ),
															esc_url( home_url() ),
															get_bloginfo( 'name' )
														) ); ?>
													</small>
												</p>
										</td>
								</tr>
						</table>
				</div>
		</body>
</html>
