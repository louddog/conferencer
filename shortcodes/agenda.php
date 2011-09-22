<?php

// TODO: Allow tabbed days
// TODO: broken layout: single day, multi-time-slot, no sessions
// TODO: empty rows and columns seem to not be showing up by default
// TODO: maybe don't use <p> tags in session grid (still use in tooltip)

/* ============================================================================

	You can override the session display function in your own template.
	In your own functions.php, define:
		conferencer_agenda_display_session($session, $options)
	
	Short Code Options --------------------------------------------------------
	
	column_type: [track, room, false]
		set which post type to use for columns
		use false to display sessions all in one column
	
	session_tooltips: [true, false]
		whether to add content that displays a tooltip on hover
	
	show_empty_rows: [true, false]
		whether to show table rows that don't have sessions
	
	show_empty_columns: [true, false]
		whether to show table columns that don't have sessions
		
	show_empty_cells: [true, false]
		if false, overrides show_empty_rows and show_empty_columns with false

	row_day_format: PHP date() format string or false
		formats the date used on day row separators
		if false, day row separators are not displayed
		
	row_time_format: PHP date() format string
		formats the date used for time slots
		
	show_row_ends: [true, false]
		if true, displays an ending time for time slots
		uses the row_time_format
		
	link_sessions: [true, false]
		whether to link session to their pages'
		
	link_speakers: [true, false]
		whether to link speakers to their pages'
		
	link_time_slots: [true, false]
		whether to link time slots to their pages'
		
	link_columns: [true, false]
		whether to link columns headers to their pages'
		
	unassigned_column_header_text: (string)
		text used in the column header for unassigned sessions
		
	unscheduled_row_text: (string)
		text used for the row of unscheduled sessions
		displayed in the day row separator, if used
		otherwise, displayed in the row's first cell (where time slots are displayed)
	
============================================================================ */

new Conferencer_Shortcode_Agenda();
class Conferencer_Shortcode_Agenda extends Conferencer_Shortcode {
	var $shortcode = 'agenda';
	var $defaults = array(
		'column_type' => 'track',
		'session_tooltips' => true,
		'show_empty_rows' => true,
		'show_empty_columns' => true,
		'show_empty_cells' => null,
		'row_day_format' => 'l, F j, Y',
		'row_time_format' => 'g:ia',
		'show_row_ends' => false,
		'link_sessions' => true,
		'link_speakers' => true,
		'link_rooms' => true,
		'link_time_slots' => true,
		'link_columns' => true,
		'unassigned_column_header_text' => 'N/A',
		'unscheduled_row_text' => 'Unscheduled',
	);

	function content($options) {
		// Set Options
		if (!in_array($options['column_type'], array('track', 'room', false))) $options['column_type'] = false;
		if ($options['show_empty_cells'] != null) $options['show_empty_rows'] = $options['show_empty_columns'] = $options['show_empty_cells'];
		$this->set_options($options);
		extract($this->options);

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
		} else echo "<p>no column type? [$column_type]</p>";
	
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
					'title' => $unassigned_column_header_text,
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
							$show_day_row = $row_day_format !== false && date('w', $row_starts) != date('w', $last_row_starts);
						
							if ($show_day_row) { ?>
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
									} else if (!$show_day_row) echo $unscheduled_row_text;
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
												$this->display_session($session);
											}
										?>
									</td>
								<?php } ?>

							<?php } else { // all sessions in one cell ?>
							
								<td class="sessions <?php if (empty($cells)) echo 'no-sessions'; ?>">
									<?php
										foreach ($cells as $session) {
											$this->display_session($session);
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
	
	function display_session($session) {
		if (function_exists('conferencer_agenda_display_session')) {
			conferencer_agenda_display_session($session, $this->options);
			return;
		}

		extract($this->options);
		?>

		<div class="session">
			<?php echo do_shortcode("
				[session_meta
					post_id='$session->ID'
					show='title,speakers".($session_tooltips ? '' : ',room')."'
					speakers_prefix='with '
					room_prefix='in '
					link_title=".($link_sessions ? 'true' : 'false')."
					link_speakers=".($link_speakers ? 'true' : 'false')."
					link_room=".($link_rooms ? 'true' : 'false')."
				]
			");	?>

			<?php if ($session_tooltips) { ?>
				<div class="session-tooltip">
					<?php echo do_shortcode("
						[session_meta
							post_id='$session->ID'
							show='title,speakers,room'
							link_all=false
						]
					"); ?>
					
					<p class="excerpt"><?php echo generate_excerpt($session); ?></p>
					<div class="arrow"></div><div class="inner-arrow"></div>
				</div>
			<?php } ?>
		
		</div>

	<?php }
	
}