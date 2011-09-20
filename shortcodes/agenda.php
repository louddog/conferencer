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
		'show_unscheduled' => true,
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
	), robustAtts($options));

	if (!in_array($options['column_type'], array('track', 'room'))) $options['column_type'] = false;
	if (!$options['show_empty_cells']) $options['show_empty_rows'] = $options['show_empty_columns'] = false;
	if (empty($options['row_day_format'])) $options['row_day_format'] = false;
	
	extract($options);
	
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

	$agenda = array();
	$unscheduled = array();
	
	foreach (Conferencer::get_list('time_slot', 'start_time_sort') as $time_slot_id => $time_slot) {
		$agenda[$time_slot_id] = array();
	}
	
	if ($column_type) {
		$column_post_counts = array();
		$column_posts = Conferencer::get_list($column_type);
		foreach ($agenda as $time_slot_id => $time_slot) {
			foreach ($column_posts as $column_post_id => $column_post) {
				$column_post_counts[$column_post_id] = 0;
				$agenda[$time_slot_id][$column_post_id] = array();
			}
		}
	}
	
	$sessions = Conferencer::get_list('session', 'title_sort');
	Conferencer::attach_speakers($sessions);
	
	foreach ($sessions as $session) {
		$time_slot_id = get_post_meta($session->ID, 'conferencer_time_slot', true);

		if ($column_type) {
			$column_id = get_post_meta($session->ID, 'conferencer_'.$column_type, true);
			if ($time_slot_id && $column_id) {
				$agenda[$time_slot_id][$column_id][$session->ID] = $session;
				$column_post_counts[$column_id]++;
			} else $unscheduled[] = $session;
		} else {
			if ($time_slot_id) $agenda[$time_slot_id][$session->ID] = $session;
			else $unscheduled[] = $session;
		}
	}
	
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
	
	$row_starts = $last_row_starts = false;
	
	ob_start();
	
	?>
	
	<div class="conferencer_agenda">
		<table class="grid">
			<?php if ($column_type) { ?>
				<thead>
					<tr>
						<th class="column_time_slot"></th>
						<?php foreach ($column_posts as $column_post_id => $column_post) { ?>
							
							<?php if (!$show_empty_columns && in_array($column_post_id, $empty_column_post_ids)) continue; ?>
							
							<th class="column_<?php echo $column_post->post_name; ?>">
								<?php if ($link_columns) { ?>
									<a href="<?php echo get_permalink($column_post->ID); ?>">
								<?php } ?>
								
									<?php echo $column_post->post_title; ?>
								
								<?php if ($link_columns) { ?>
									</a>
								<?php } ?>
							</th>
						<?php } ?>
					</tr>
				</thead>
			<?php } ?>
			
			<tbody>
				<?php foreach ($agenda as $time_slot_id => $cells) { ?>
				
					<?php
						if (!$time_slot_id) continue; // no time slot
						
						$last_row_starts = $row_starts;
						$row_starts = get_post_meta($time_slot_id, 'conferencer_starts', true);
						$row_ends = get_post_meta($time_slot_id, 'conferencer_ends', true);
						
						if ($row_day_format && date('w', $row_starts) != date('w', $last_row_starts)) { ?>
							<tr class="day">
								<td<?php if ($column_type) echo ' colspan="'.(count($column_posts) + 1).'"'; ?>>
									<?php echo date($row_day_format, $row_starts); ?>
								</td>
							</tr>
						<?php }
					
						$non_session = get_post_meta($time_slot_id, 'conferencer_non_session', true);
						$no_sessions = deep_empty($cells);
					
						$classes = array();
						if ($non_session) $classes[] = 'non-session';
						else if ($no_sessions) $classes[] = 'no-sessions';
						$classes = count($classes) ? ' class="'.implode(' ', $classes).'"' : '';
					?>
				
					<tr<?php echo $classes; ?>>
					
						<td>
							<?php if ($link_time_slots) { ?>
								<a href="<?php echo get_permalink($time_slot_id); ?>">
							<?php } ?>
								<?php echo date($row_time_format, $row_starts); ?>
								<?php if ($show_row_ends) { ?>
									&ndash; <?php echo date($row_time_format, $row_ends); ?>
								<?php } ?>
							<?php if ($link_time_slots) { ?>
								</a>
							<?php } ?>
						</td>

						<?php if ($non_session) { ?>

							<td<?php if ($column_type) echo ' colspan="'.count($column_posts).'"'; ?>>
								<?php if ($link_time_slots) { ?>
									<a href="<?php echo get_permalink($time_slot_id); ?>">
								<?php } ?>
									<?php echo get_the_title($time_slot_id); ?>
								<?php if ($link_time_slots) { ?>
									</a>
								<?php } ?>
							</td>

						<?php } else if ($column_type) { ?>

							<?php foreach ($cells as $cell_sessions) { ?>
								<td class="<?php if (empty($cell_sessions)) echo 'no-sessions'; ?>">
									<?php
										foreach ($cell_sessions as $session) {
											conferencer_agenda_display_session($session, $options);
										}
									?>
								</td>
							<?php } ?>

						<?php } else { ?>
							
							<td class="<?php if (empty($cells)) echo 'no-sessions'; ?>">
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
	
		<?php if ($show_unscheduled && count($unscheduled)) { ?>
			<h3>Unscheduled</h3>
			<ul class="unscheduled">
				<?php foreach ($unscheduled as $session) { ?>
					<li><?php conferencer_agenda_display_session($session, $options); ?></li>
				<?php } ?>
			</ul>
		<?php } ?>
		
	</div> <!-- .conferencer_agenda -->
	
	<?php
	
	return ob_get_clean();
}