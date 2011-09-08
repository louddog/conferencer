<?php

function conferencer_agenda_shortcode($atts) {
	extract(shortcode_atts(array(
	), $atts ));

	$agenda = array();

	$time_slots = Conferencer::get_list('time_slot', 'start_time_sort');
	$rooms = Conferencer::get_list('room');

	foreach ($time_slots as $time_slot) {
		foreach ($rooms as $room) {
			$agenda[$time_slot->ID][$room->ID] = array();
		}
	}

	foreach (Conferencer::get_list('session', 'title_sort') as $session) {
		$time_slot_id = get_post_meta($session->ID, 'session_time_slot', true);
		$room_id = get_post_meta($session->ID, 'session_room', true);
		$agenda[$time_slot_id][$room_id][$session->ID] = $session;
	}
		
	ob_start();
	?>
	
	<table class="conferencer_agenda">
		<thead>
			<tr>
				<th></th>
				<?php foreach ($rooms as $room) { ?>
					<th>
						<a href="<?php echo get_permalink($room->ID); ?>">
							<?php echo $room->post_title; ?>
						</a>
					</th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($agenda as $time_slot_id => $rooms) { ?>
				<tr>
					<td>
						<a href="<?php echo get_permalink($time_slot_id); ?>">
							<?php echo date('g:ia', get_post_meta($time_slot_id, 'conferencer_starts', true)); ?>
						</a>
					</td>

					<?php if (get_post_meta($time_slot_id, 'conferencer_no_sessions', true)) { ?>

						<td colspan="<?php echo count($rooms); ?>">
							<a href="<?php echo get_permalink($time_slot_id); ?>">
								<?php echo get_the_title($time_slot_id); ?>
							</a>
						</td>

					<?php } else { ?>

						<?php foreach ($rooms as $sessions) { ?>
							<td>
								<?php foreach ($sessions as $session_id => $session) { ?>
									<div>
										<a href="<?php echo get_permalink($session->ID); ?>">
											<?php echo $session->post_title; ?>
										</a>
									</div>
								<?php } ?>
							</td>
						<?php } ?>

					<?php } ?>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	
	<?php
	
	return ob_get_clean();
}

add_shortcode('agenda', 'conferencer_agenda_shortcode');
