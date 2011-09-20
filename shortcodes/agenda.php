<?php

/* ============================================================================

	You can override the cell display function in your own template's
	functions.php.  Simply define:
		conferencer_agenda_display_session($session, $options = array())

============================================================================ */

add_shortcode('agenda', 'conferencer_agenda_shortcode');

function conferencer_agenda_shortcode($options) {
	$options = shortcode_atts(array(
		'column_type' => 'track',
		'session_tooltips' => true,
		'show_empty_cells' => true,
		'show_empty_rows' => true,
		'show_empty_columns' => true,
		'days_on_one_table' => true,
		'row_day_format' => 'l, F j, Y',
		'row_time_format' => 'g:ia',
		'show_row_ends' => false,
		'link_sessions' => true,
		'link_speakers' => true,
		'link_time_slots' => true,
		'link_columns' => true,
		'unscheduled_column_header_text' => 'N/A',
		'unscheduled_row_text' => 'Unscheduled',
	), robustAtts($options));

	// Massage Options
	
	if (!in_array($options['column_type'], array('track', 'room'))) $options['column_type'] = false;
	if (!$options['show_empty_cells']) $options['show_empty_rows'] = $options['show_empty_columns'] = false;
	if (empty($options['row_day_format'])) $options['row_day_format'] = false;
	
	extract($options);
	
	// If not defined, define a function that outputs a session in the agend
	
	if (!function_exists('conferencer_agenda_display_session')) {
		function conferencer_agenda_display_session($session, $options = array()) { extract($options); ?>

			<div class="session">
				<p class="title">
					<?php if ($link_sessions) { ?>
						<a href="<?php echo get_permalink($session->ID); ?>">
					<?php } ?>
						<?php echo $session->post_title; ?>
					<?php if ($link_sessions) { ?>
						</a>
					<?php } ?>
				</p>
			
				<p class="speakers">
					<?php echo comma_seperated($session->speakers, $link_speakers); ?>
				</p>

				<?php if ($session_tooltips) { ?>
					<div class="session-tooltip">
						<h3 class="title"><?php echo $session->post_title; ?></h3>
						<p class="speakers"><?php echo comma_seperated($session->speakers, false); ?></p>
						<p class="excerpt"><?php echo $session->post_excerpt; ?></p>
						<div class="arrow"></div>
						<div class="inner-arrow"></div>
					</div>
				<?php } ?>
				
			</div>

		<?php }
	}

	// Define main agenda variable

	$agenda = array();
	
	// Fill agenda with empty time slot rows
	
	foreach (Conferencer::get_list('time_slot', 'start_time_sort') as $time_slot_id => $time_slot) {
		$agenda[$time_slot_id] = array();
	}
	$agenda[0] = array(); // for unscheduled time slots
	
	// If the agenda is split into columns, fill rows with empty "cell" arrays
	
	if ($column_type) {
		$column_post_counts = array();
		$column_posts = Conferencer::get_list($column_type);
		
		foreach ($agenda as $time_slot_id => $time_slot) {
			foreach ($column_posts as $column_post_id => $column_post) {
				$column_post_counts[$column_post_id] = 0;
				$agenda[$time_slot_id][$column_post_id] = array();
			}
			$agenda[$time_slot_id][0] = array();
		}
	}
	
	// Get all session information
	
	$sessions = Conferencer::get_list('session', 'title_sort');
	Conferencer::attach_speakers($sessions);
	
	// Put sessions into agenda variable
	
	foreach ($sessions as $session) {
		$time_slot_id = get_post_meta($session->ID, 'conferencer_time_slot', true);
		if (!$time_slot_id) $time_slot_id = 0;

		if ($column_type) {
			$column_id = get_post_meta($session->ID, 'conferencer_'.$column_type, true);
			if (!$column_id) $column_id = 0;
			
			$agenda[$time_slot_id][$column_id][$session->ID] = $session;
			$column_post_counts[$column_id]++;
		} else {
			$agenda[$time_slot_id][$session->ID] = $session;
		}
	}
	
	// Conditionally remove empty rows and columns
	
	if (!$show_empty_rows) {
		foreach ($agenda as $time_slot_id => $cells) {
			$non_session = get_post_meta($time_slot_id, 'conferencer_non_session', true);
			if (!$non_session && deep_empty($cells)) unset($agenda[$time_slot_id]);
		}
	}
	
	if (!$show_empty_columns) {
		$empty_column_post_ids = array();
		foreach ($column_posts as $column_post_id => $column_post) {
			if (!$column_post_counts[$column_post_id]) $empty_column_post_ids[] = $column_post_id;
		}
		
		foreach ($agenda as $time_slot_id => $cells) {
			foreach ($empty_column_post_ids as $empty_column_post_id) {
				unset($agenda[$time_slot_id][$empty_column_post_id]);
			}
		}
	}
	
	// Set up column headers
	
	if ($column_type) {
		$column_headers = array();
		
		// post column headers
		foreach ($column_posts as $column_post) {
			if (!$show_empty_columns && in_array($column_post->ID, $empty_column_post_ids)) continue;
			
			$column_headers[] = array(
				'title' => $column_post->post_title,
				'class' => 'column_'.$column_post->post_name,
				'link' => $link_columns ? get_permalink($column_post->ID) : false,
			);
		}
		
		if (count($column_post_counts[0])) {
			// extra column header for sessions not assigned to a column
			$column_headers[] = array(
				'title' => $unscheduled_column_header_text,
				'class' => 'column_not_applicable',
				'link' => false,
			);
		} else {
			// remove cells if no un-assigned sessions
			foreach ($agenda as $time_slot_id => $cells) {
				unset($agenda[$time_slot_id][0]);
			}
		}
	}
	
	// Remove unscheduled time slot, if without sessions
	if (deep_empty($agenda[0])) unset($agenda[0]);

	// Start buffering output

	ob_start();
	
	?>
	
	<div class="conferencer_agenda">
		<table class="grid">
			
			<?php // Table head ============================================ ?>

			<?php if ($column_type) { ?>
				<thead>
					<tr>
						<th class="column_time_slot"></th>
						<?php foreach ($column_headers as $column_header) { ?>
							
							<th class="<?php echo $column_header['class']; ?>">
								<?php
									$html = $column_header['title'];
									if ($column_header['link']) $html = "<a href='".$column_header['link']."'>$html</a>";
									echo $html;
								?>
							</th>
							
						<?php } ?>
					</tr>
				</thead>
			<?php } ?>
			
			<?php // Table body ============================================ ?>
			
			<tbody>
				<?php $row_starts = $last_row_starts = false; ?>
				<?php foreach ($agenda as $time_slot_id => $cells) { ?>
				
					<?php
						// Set up row information
					
						$last_row_starts = $row_starts;
						$row_starts = get_post_meta($time_slot_id, 'conferencer_starts', true);
						$row_ends = get_post_meta($time_slot_id, 'conferencer_ends', true);
						$non_session = get_post_meta($time_slot_id, 'conferencer_non_session', true);
						$no_sessions = deep_empty($cells);
						
						// Show row seperators for days
						
						if ($row_day_format && date('w', $row_starts) != date('w', $last_row_starts)) { ?>
							<tr class="day">
								<td colspan="<?php echo $column_type ? count($column_headers) + 1 : 2; ?>">
									<?php echo $row_starts ? date($row_day_format, $row_starts) : $unscheduled_row_text; ?>
								</td>
							</tr>
						<?php }
						
						// Set row classes

						$classes = array();
						if ($non_session) $classes[] = 'non-session';
						else if ($no_sessions) $classes[] = 'no-sessions';
					?>
				
					<tr<?php output_classes($classes); ?>>
					
						<?php // Time slot column -------------------------- ?>
					
						<td class="time_slot">
							<?php
								if ($time_slot_id) {
									$html = date($row_time_format, $row_starts);
									if ($show_row_ends) $html .= " &ndash; ".date($row_time_format, $row_ends);
									if ($link_time_slots) $html = "<a href='".get_permalink($time_slot_id)."'>$html</a>";
									echo $html;
								}
							?>
						</td>
						
						<?php // Display session cells --------------------- ?>

						<?php if ($non_session) { // display a non-sessioned time slot ?>

							<td class="sessions" colspan="<?php echo $column_type ? count($column_headers) : 1; ?>">
								<?php if ($link_time_slots) { ?>
									<a href="<?php echo get_permalink($time_slot_id); ?>">
								<?php } ?>
									<?php echo get_the_title($time_slot_id); ?>
								<?php if ($link_time_slots) { ?>
									</a>
								<?php } ?>
							</td>

						<?php } else if ($column_type) { // if split into columns, multiple cells  ?>

							<?php foreach ($cells as $cell_sessions) { ?>
								<td class="sessions <?php if (empty($cell_sessions)) echo 'no-sessions'; ?>">
									<?php
										foreach ($cell_sessions as $session) {
											conferencer_agenda_display_session($session, $options);
										}
									?>
								</td>
							<?php } ?>

						<?php } else { // all sessions in one cell ?>
							
							<td class="sessions <?php if (empty($cells)) echo 'no-sessions'; ?>">
								<?php
									foreach ($cells as $session) {
										conferencer_agenda_display_session($session, $options);
									}
								?>
							</td>
							
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	
	</div> <!-- .conferencer_agenda -->
	
	<?php
	
	// Retrieve and return buffer
	
	return ob_get_clean();
}