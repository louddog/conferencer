<?php

/* ============================================================================

	You can override the session display function in your own template.
	In your own functions.php, define:
		conferencer_agenda_display_session($session, $options)
	
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
		'tabs' => 'days',
		'tab_day_format' => 'M. j, Y',
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
	
	function prep_options() {
		parent::prep_options();
		
		if (!in_array($this->options['column_type'], array('track', 'room'))) {
			$this->options['column_type'] = false;
		}
		
		if ($this->options['show_empty_cells'] != null) {
			$this->options['show_empty_rows'] = $this->options['show_empty_cells'];
			$this->options['show_empty_columns'] = $this->options['show_empty_cells'];
		}
	}

	function content() {
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
		
		// Remove empty unscheduled rows
		
		if (deep_empty($agenda[0])) unset($agenda[0]);
	
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

		// Set up tabs
	
		if ($tabs) {
			$tab_headers = array();
		
			foreach ($agenda as $time_slot_id => $cells) {
				if ($tabs == 'days') {
					if ($starts = get_post_meta($time_slot_id, 'conferencer_starts', true)) {
						$tab_headers[] = get_day($starts);
					} else $tab_headers[] = 0;
				}
			}
		
			$tab_headers = array_unique($tab_headers);
			
			if (count($tab_headers) < 2) $tabs = false;
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
			
			<?php if ($tabs) { ?>
				<div class="conferencer_tabs">
				<ul class="tabs">
					<?php foreach ($tab_headers as $tab_header) { ?>
						<li>
							<?php if ($tabs == 'days') { ?>
								<a href="#conferencer_agenda_tab_<?php echo $tab_header; ?>">
									<?php echo $tab_header ? date($tab_day_format, $tab_header) : $unscheduled_row_text; ?>
								</a>
							<?php } ?>
						</li>
					<?php } ?>
				</ul>
			<?php } else { ?>
				<table class="grid">
					<?php if ($column_type) $this->display_headers($column_headers); ?>
					<tbody>
			<?php } ?>
			
					<?php $row_starts = $last_row_starts = $second_table = false; ?>
					<?php foreach ($agenda as $time_slot_id => $cells) { ?>
				
						<?php
							// Set up row information
					
							$last_row_starts = $row_starts;
							$row_starts = get_post_meta($time_slot_id, 'conferencer_starts', true);
							$row_ends = get_post_meta($time_slot_id, 'conferencer_ends', true);
							$non_session = get_post_meta($time_slot_id, 'conferencer_non_session', true);
							$no_sessions = deep_empty($cells);
						
							// Show day seperators
							$show_next_day = $row_day_format !== false && date('w', $row_starts) != date('w', $last_row_starts);
						
							if ($show_next_day) { ?>
								
								<?php if ($tabs) { ?>

									<?php if ($second_table) { ?>
											</tbody>
										</table>
										 <!-- #conferencer_agenda_tab_xxx --> </div>
									<?php } else $second_table = true; ?>

									<div id="conferencer_agenda_tab_<?php echo get_day($row_starts); ?>">
									<table class="grid">
										<?php if ($column_type) $this->display_headers($column_headers); ?>
										<tbody>
								<?php } else { ?>
									<tr class="day">
										<td colspan="<?php echo $column_type ? count($column_headers) + 1 : 2; ?>">
											<?php echo $row_starts ? date($row_day_format, $row_starts) : $unscheduled_row_text; ?>
										</td>
									</tr>
								<?php } ?>
								
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
			
			<?php if ($tabs) { ?>
				 <!-- #conferencer_agenda_tab_xxx --> </div>
				</div> <!-- .conferencer_agenda_tabs -->
			<?php } ?>
	
		</div> <!-- .conferencer_agenda -->
	
		<?php
	
		// Retrieve and return buffer
	
		return ob_get_clean();
	}
	
	function display_headers($column_headers) { ?>
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
	<?php }
	
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