<?php

/* ============================================================================

	You can override the cell display function in your own template's
	functions.php.  Simply define:
		conferencer_agenda_display_session($session, $options = array())

============================================================================ */

add_shortcode('agenda', 'conferencer_agenda_shortcode');

function conferencer_agenda_shortcode($options) {
	$options = shortcode_atts(array(
		'column_type' => 'track', // TODO: implement other columns
		'show_unscheduled' => true,
		'session_tooltips' => true,
		'show_empty_rows' => true,
	), robustAtts($options));

	if (in_array($options['column_type'], array('none'))) $options['column_type'] = false;
	
	extract($options);
	
	if (!function_exists('conferencer_agenda_display_session')) {
		function conferencer_agenda_display_session($session, $options = array()) { extract($options); ?>

			<div class="session">
				<p class="title">
					<a href="<?php echo get_permalink($session->ID); ?>">
						<?php echo $session->post_title; ?>
					</a>
				</p>
			
				<p class="speakers"><?php echo comma_sep_links($session->speakers); ?></p>

				<?php if ($session_tooltips) { ?>
					<div class="session-tooltip">
						<h3 class="title"><?php echo $session->post_title; ?></h3>
						<p class="speakers"><?php echo comma_sep_titles($session->speakers); ?></p>
						<p class="excerpt"><?php echo $session->post_excerpt; ?></p>
						<div class="arrow"></div>
						<div class="inner-arrow"></div>
					</div>
				<?php } ?>
				
			</div>

		<?php }
	}

	$time_slots = Conferencer::get_list('time_slot', 'start_time_sort');
	$tracks = Conferencer::get_list('track');
	$sessions = Conferencer::get_list('session', 'title_sort');
	$speakers = Conferencer::get_list('speaker');
	
	$agenda = array();
	$unscheduled = array();
	
	foreach ($time_slots as $time_slot_id => $time_slot) {
		$agenda[$time_slot_id] = array();
		
		if ($column_type == 'track') {
			foreach ($tracks as $track_id => $track) {
				$agenda[$time_slot_id][$track_id] = array();
			}
		}
	}
	
	foreach ($sessions as $session) {
		$time_slot_id = get_post_meta($session->ID, 'conferencer_time_slot', true);

		$session->speakers = array();
		$speaker_ids = unserialize(get_post_meta($session->ID, 'conferencer_speakers', true));
		if (!$speaker_ids) $speaker_ids = array();
		foreach ($speaker_ids as $speaker_id) {
			$session->speakers[$speaker_id] = $speakers[$speaker_id];
		}
		
		if ($column_type) {
			if ($column_type == 'track') $column_id = get_post_meta($session->ID, 'conferencer_track', true);
			
			if ($time_slot_id && $column_id) $agenda[$time_slot_id][$column_id][$session->ID] = $session;
			else $unscheduled[] = $session;
		} else {
			if ($time_slot_id) $agenda[$time_slot_id][$session->ID] = $session;
			else $unscheduled[] = $session;
		}
	}
		
	ob_start();
	?>
	
	<div class="conferencer_agenda">
		<table class="grid">
			<?php if ($column_type) { ?>
				<thead>
					<tr>
						<th></th>
						<?php foreach ($tracks as $track) { ?>
							<th class="conferencer_track_<?php echo $track->post_name; ?>">
								<a href="<?php echo get_permalink($track->ID); ?>">
									<?php echo $track->post_title; ?>
								</a>
							</th>
						<?php } ?>
					</tr>
				</thead>
			<?php } ?>
			<tbody>
				<?php foreach ($agenda as $time_slot_id => $column_ids) { ?>
				
					<?php
						if (!$time_slot_id) continue; // no time slot
					
						$non_session = get_post_meta($time_slot_id, 'conferencer_non_session', true);
						$no_sessions = deep_empty($column_ids);
					
						if (!$non_session && $no_sessions && !$show_empty_rows) continue;

						$classes = array();
						if ($non_session) $classes[] = 'non-session';
						else if ($no_sessions) $classes[] = 'no-sessions';
						$classes = count($classes) ? ' class="'.implode(' ', $classes).'"' : '';
					?>
				
					<tr<?php echo $classes; ?>>
					
						<td>
							<a href="<?php echo get_permalink($time_slot_id); ?>">
								<?php echo date('g:ia', get_post_meta($time_slot_id, 'conferencer_starts', true)); ?>
							</a>
						</td>

						<?php if ($non_session) { ?>

							<td colspan="<?php echo count($tracks); ?>">
								<a href="<?php echo get_permalink($time_slot_id); ?>">
									<?php echo get_the_title($time_slot_id); ?>
								</a>
							</td>

						<?php } else if ($column_type) { ?>

							<?php foreach ($column_ids as $column_sesssions) { ?>
								<td class="<?php if (empty($column_sesssions)) echo 'no-sessions'; ?>">
									<?php
										foreach ($column_sesssions as $session) {
											conferencer_agenda_display_session($session, $options);
										}
									?>
								</td>
							<?php } ?>

						<?php } else { ?>
							
							<td class="<?php if (empty($column_ids)) echo 'no-sessions'; ?>">
								<?php
									foreach ($column_ids as $session) {
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