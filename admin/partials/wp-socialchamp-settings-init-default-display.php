<?php
// function wpsc_checked($key){
// echo checked(1 , get_option('wpsc_' . $key));
// }
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="row">
	<div class="col-lg-12 col-md-12">
		<!--Sub Tab Content Start-->
		<ul class="nav nav-tabs sub-nav-tabs" id="sc-publish-sub-tabs" role="tablist-sub">
			<?php
			foreach ( $socialchampSubtab as $champSubKey => $champSubtab ) :
				$prefix = $postType . '_' . $champSubKey;
				?>
			<li class="nav-item">
				<a class="nav-link <?php echo $champSubKey == 'publish' ? 'active' : ''; ?>" id="<?php echo esc_attr( $prefix ); ?>-tab" data-toggle="tab" href="#<?php echo esc_attr( $prefix ); ?>" role="tab" aria-controls="<?php echo esc_attr( $champSubKey ); ?>" aria-selected="true">
					<?php echo esc_html( $champSubtab['title'] ); ?>
					<?php echo get_option( 'wpsc_' . $prefix . '_enabled', 0 ) == 0 ? '' : '<i class="fa fa-check-circle" aria-hidden="true"></i>'; ?>
				</a>
			</li>
			<?php endforeach; ?>
			<!--<li class="nav-item">
				<a class="nav-link" id="update-tab" data-toggle="tab" href="#update" role="tab" aria-controls="update" aria-selected="false">
					Update
					<i class="fa fa-check-circle" aria-hidden="true"></i>
				</a>
			</li>-->
		</ul><!--Sub Tabs Listed End-->
	</div>
	<div class="col-lg-12 col-md-12">
		<!--Tabs Content Start-->
		<div class="tab-content sc-subtabs-content" id="sc-publish-sub-tabs">
			<?php
			foreach ( $socialchampSubtab as $champSubKey => $champSubtab ) :
				$prefix = $postType . '_' . $champSubKey;
				?>
			<div class="tab-pane fade <?php echo $champSubKey == 'publish' ? 'show active' : ''; ?>" id="<?php echo esc_attr( $prefix ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $prefix ); ?>-tab">
				<div class="sc-postbox">
					<div class="sc-post-top">
						<h5 class="d-flex">Defaults: <?php echo esc_html( $champSubtab['title'] ); ?>
							<label for="default_<?php echo esc_attr( $prefix ); ?>_enabled" class="ml-auto">
								<input <?php $this->wpsc_checked( $prefix . '_enabled' ); ?> type="checkbox" value="1" id="default_<?php echo esc_attr( $prefix ); ?>_enabled" class="enable" name="<?php echo esc_attr( $prefix ); ?>_enabled">Enabled
							</label>
						</h5>
						<p> <?php echo esc_html( str_replace( ':singular_name', ucfirst( $champSidebar['singular_name'] ), $champSubtab['description'] ) ); ?> <?php echo esc_html( $champSubtab['action'] ); ?></p>
						<p><a class="btn btn-sm btn-primary socialchampAddStatus" data-key="<?php echo esc_attr( $postType ); ?>" data-subkey="<?php echo esc_attr( $champSubKey ); ?>"><i class="fa fa-plus"></i></a></p>
					</div>
					<div class="sc-content-wrap">
						<div class="sc-plugin-conent">
							<div id="default-<?php echo esc_attr( $postType ); ?>-<?php echo esc_attr( $champSubKey ); ?>-statuses" class="statuses">
								<?php
								$options = get_option( 'wpsc_' . $prefix );
								if ( ! $options ) :
									?>
								<div class="sc-wpzinc-option sortable first socialchampStatusCounter">
									<!-- Count + Delete -->
									<div class="left number">
										<a href="#" class="count" title="Drag status to reorder">#1</a>
										<a href="#" class="dashicons dashicons-trash delete socialchampDelete" title="Delete Condition"></a>
									</div>

									<!-- Status -->
									<div class="right status statusSection">
										<!-- Tags and Feat. Image -->
										<div class="tags-featured-image">
											<!-- Use Feat. Image -->
											<select name="<?php echo esc_attr( $prefix ); ?>[image][]" size="1" class="right socialchampImage">
												<option value="-1">No Image</option>
												<option value="0" selected="selected">
													Use OpenGraph Settings
												</option>
												<option value="2">
													Use Featured image
												</option>
											</select>

											<!-- Tags -->
											<select size="1" class="left tags socialchampTags" name="">
												<option value="">--- Insert Tag ---</option>
												<optgroup label="post">
													<option value="{sitename}">Site Name</option>
													<option value="{title}">Post Title</option>
													<option value="{excerpt}">Post Excerpt (Full)</option>
													<option value="{excerpt(?)}">Post Excerpt (Character Limited)</option>
													<option value="{excerpt(?_words)}">Post Excerpt (Word Limited)</option>
													<option value="{content}">Post Content (Full)</option>
													<option value="{content(?)}">Post Content (Character Limited)</option>
													<option value="{content(?_words)}">Post Content (Word Limited)</option>
													<option value="{date}">Post Date</option>
													<option value="{url}">Post URL</option>
													<option value="{id}">Post ID</option>
												</optgroup>
												<optgroup label="taxonomy">
													<option value="{taxonomy_category}">Taxonomy: Category: Hashtag Format</option>
													<option value="{taxonomy_post_tag}">Taxonomy: Tag: Hashtag Format</option>
												</optgroup>
											</select>
										</div>

										<!-- Status Message -->
										<div class="full">
											<textarea name="<?php echo esc_attr( $prefix ); ?>[content][]" rows="3" class="widefat autosize-js" style="overflow: hidden; overflow-wrap: break-word; resize: none; height: 66px;">{title} {url}</textarea>
										</div>

										<!-- Scheduling -->
										<div class="full">
											<select name="<?php echo esc_attr( $prefix ); ?>[queue_bottom][]" size="1" class="schedule widefat">
												<option value="now" selected="selected">Send Immediately</option>
												<option value="queue_top">Add to Top of Social Champ Queue</option>
												<option value="queue_bottom">Add to End of Social Champ Queue</option>
											</select>
										</div>
									</div>
								</div>
									<?php
								else :
									foreach ( $options as $optionKey => $option ) :
										?>
										<div class="sc-wpzinc-option sortable first socialchampStatusCounter">
											<!-- Count + Delete -->
											<div class="left number">
												<a href="javascript:void(0)" class="count" title="Drag status to reorder">#<?php echo absint( $optionKey ) + 1; ?></a>
												<a href="javascript:void(0)" class="dashicons dashicons-trash delete socialchampDelete" title="Delete Condition" style="display: <?php echo $optionKey == 0 ? 'none' : 'block'; ?>"></a>
											</div>

											<!-- Status -->
											<div class="right status statusSection">
												<!-- Tags and Feat. Image -->
												<div class="tags-featured-image">
													<!-- Use Feat. Image -->
													<select name="<?php echo esc_attr( $prefix ); ?>[image][]" size="1" class="right socialchampImage">
														<option <?php echo $option['image'] == -1 ? 'selected' : ''; ?> value="-1">No Image</option>
														<option <?php echo $option['image'] == 0 ? 'selected' : ''; ?> value="0">Use OpenGraph Settings</option>
														<option <?php echo $option['image'] == 2 ? 'selected' : ''; ?> value="2">Use Featured image</option>
													</select>

													<!-- Tags -->
													<select size="1" class="left tags socialchampTags" name="">
														<option value="">--- Insert Tag ---</option>
														<optgroup label="post">
															<option value="{sitename}">Site Name</option>
															<option value="{title}">Post Title</option>
															<option value="{excerpt}">Post Excerpt (Full)</option>
															<option value="{excerpt(?)}">Post Excerpt (Character Limited)</option>
															<option value="{excerpt(?_words)}">Post Excerpt (Word Limited)</option>
															<option value="{content}">Post Content (Full)</option>
															<option value="{content(?)}">Post Content (Character Limited)</option>
															<option value="{content(?_words)}">Post Content (Word Limited)</option>
															<option value="{date}">Post Date</option>
															<option value="{url}">Post URL</option>
															<option value="{id}">Post ID</option>
														</optgroup>
														<optgroup label="taxonomy">
															<option value="{taxonomy_category}">Taxonomy: Category: Hashtag Format</option>
															<option value="{taxonomy_post_tag}">Taxonomy: Tag: Hashtag Format</option>
														</optgroup>
													</select>
												</div>

												<!-- Status Message -->
												<div class="full">
													<textarea name="<?php echo esc_attr( $prefix ); ?>[content][]" rows="3" class="widefat autosize-js" style="overflow: hidden; overflow-wrap: break-word; resize: none; height: 66px;"><?php echo esc_html( $option['content'] ); ?></textarea>
												</div>

												<!-- Scheduling -->
												<div class="full">
													<select name="<?php echo esc_attr( $prefix ); ?>[queue_bottom][]" size="1" class="schedule widefat">
														<option value="now" <?php echo $option['queue_bottom'] == 'now' ? 'selected' : ''; ?> >Send Immediately</option>
														<option value="queue_top" <?php echo $option['queue_bottom'] == 'queue_top' ? 'selected' : ''; ?> >Add to Top of Social Champ Queue</option>
														<option value="queue_bottom" <?php echo $option['queue_bottom'] == 'queue_bottom' ? 'selected' : ''; ?> >Add to End of Social Champ Queue</option>
													</select>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>

							</div>
						</div>

					</div>

				</div>
			</div>
			<?php endforeach; ?>


		</div><!--Tabs Content End-->
	</div>
</div>
