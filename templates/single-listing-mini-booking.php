<?php
// The template loader passes in the id inside an object called $data. 
var_dump($data);

$post = get_post($data->post_id);
$post_info = $post; // Some of the code, which we want to use as-is and not change, uses this object so we duplicate the $post into it
$post_id = $data->post_id; // Used by the widget code that we are using here
$listing_logo = get_post_meta($post->ID, '_listing_logo', true); ?>
<div style="text-align: center;">
    <!-- Titlebar -->
    <div id="titlebar" style="display: inline-block;">
        <?php
        if ($listing_logo) { ?>
            <div class="listing-logo"> <img src="<?php echo $listing_logo; ?>" alt=""></div>
        <?php } ?>
    </div>
    <div style="text-align: center;">
        <h2><?php the_title(); ?></h2>
        <?php if (get_the_listing_address()) : ?>
            <span>
                <a class="listing-address">
                    <i class="fa fa-map-marker"></i>
                    <?php the_listing_address(); ?>
                </a>
            </span> <br>
        <?php endif; ?>
    </div>
	<input type="hidden" id="listing_type" value="service">
	<input type="hidden" id="listing_id" value="<?php echo $post->ID?>">
</div>


<div class="booking-widget listing-widget margin-bottom-30">



                <!--  The widget code on the main form -->

                <?php

        		// Hardcoding the heading as shown on the screenshot but could code here to use the same name as the widget
                // that appears on the sidebar on the normal listing page
                // $title  = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
		        $title = "Booking";

                // Checks that this listing has the boooking status toggle switched on and if not exits. On this page we probably
                // want this to load the standard listing page, otherwise we'll be displaying nothing here. Check what Bruno wants it
                // to do, other option is show home page or a specific "not found" page for when someone uses the link for a listing
                // for which booking is not available
                $_booking_status = get_post_meta($post_id, '_booking_status', true); {
	    			error_log($_booking_status);
		    		if (!$_booking_status) {
				    	error_log("NOT BOOKING STATUS");
				    	return;
				    }
			    }

                // When the widget is used on the sidebar the sidebar passes args to it to be used for formatting. We hard code them
                // here as we're using the widget outside of the sidebar.
                $before_widget = '<div id="%1$s" class="mrj-listing-widget %2$s">';
                $after_widget = '</div>';
                $before_title = '<h3 class="widget-title margin-bottom-35">';
                $after_title = '</h3>';

                echo $before_widget;
        
                // We don't want to usual title, going to output our own where it's wanted        
                //		if ($title) {
        	    //    		error_log(__FILE__ . ' ' . __LINE__);
        		//        	echo $before_title . '<i class="fa fa-calendar-check"></i> ' . $title . $after_title;
        		//        }

		        $days_list = array(
		        	0	=> __('Monday', 'listeo_core'),
		        	1 	=> __('Tuesday', 'listeo_core'),
		        	2	=> __('Wednesday', 'listeo_core'),
		        	3 	=> __('Thursday', 'listeo_core'),
		        	4 	=> __('Friday', 'listeo_core'),
		        	5 	=> __('Saturday', 'listeo_core'),
		        	6 	=> __('Sunday', 'listeo_core'),
		        );

            	$post_meta = get_post_meta($post->ID);
	
				error_log("here 1");

				// get slots and check if not empty

				if (isset($post_meta['_slots_status'][0]) && !empty($post_meta['_slots_status'][0])) {
					if (isset($post_meta['_slots'][0])) {
						$correctedSlotsString = str_replace(['-', '-'], '-', $post_meta['_slots'][0]);
		
						$slots = json_decode($correctedSlotsString);
		
						// Check for hyphen in the corrected string
						if (strpos($correctedSlotsString, '-') === false) {
							$slots = false;
						}
					} else {
						$slots = false;
					}
				} else {
					$slots = false;
				}

    		// get opening hours
    		if (isset($post_meta['_opening_hours'][0])) {
    			$opening_hours = json_decode($post_meta['_opening_hours'][0], true);
    		}

			// Need this for the context of the widget code on a plain page and not in an object
            $thisbookings = new Listeo_Core_Bookings_Calendar;

        if ($post_meta['_listing_type'][0] == 'rental' || $post_meta['_listing_type'][0] == 'service') {

			// get reservations for next 10 years to make unable to set it in datapicker
			if ($post_meta['_listing_type'][0] == 'rental') {
				$records = $thisbookings->get_bookings(
					date('Y-m-d H:i:s'),
					date('Y-m-d H:i:s', strtotime('+3 years')),
					array('listing_id' => $post_info->ID, 'type' => 'reservation'),
					$by = 'booking_date',
					$limit = '',
					$offset = '',
					$all = '',
					$listing_type = 'rental'
			);
		} else {

			$records = $thisbookings->get_bookings(
					date('Y-m-d H:i:s'),
					date('Y-m-d H:i:s', strtotime('+3 years')),
					array('listing_id' => $post_info->ID, 'type' => 'reservation'),
					'booking_date',
					$limit = '',
					$offset = '',
					'owner'
				);
		}

		//var_dump($records);

			// store start and end dates to display it in the widget
			$wpk_start_dates = array();
			$wpk_end_dates = array();

			var_dump(count($records));

			if (!empty($records)) {
				foreach ($records as $record) {

					if ($post_meta['_listing_type'][0] == 'rental') {
						// when we have one day reservation
						if ($record['date_start'] == $record['date_end']) {
							$wpk_start_dates[] = date('Y-m-d', strtotime($record['date_start']));
							$wpk_end_dates[] = date('Y-m-d', strtotime($record['date_start'] . ' + 1 day'));
						} else {
							/**
							 * Set the date_start and date_end dates and fill days in between as disabled
							 */
							$wpk_start_dates[] = date('Y-m-d', strtotime($record['date_start']));
							$wpk_end_dates[] = date('Y-m-d', strtotime($record['date_end']));

							$period = new DatePeriod(
								new DateTime(date('Y-m-d', strtotime($record['date_start'] . ' + 1 day'))),
								new DateInterval('P1D'),
								new DateTime(date('Y-m-d', strtotime($record['date_end']))) //. ' +1 day') ) )
							);

							foreach ($period as $day_number => $value) {
								$disabled_dates[] = $value->format('Y-m-d');
							}
						}
					} else {
						// when we have one day reservation
						if ($record['date_start'] == $record['date_end']) {
							$disabled_dates[] = date('Y-m-d', strtotime($record['date_start']));
						} else {

							// if we have many dats reservations we have to add every date between this days
							$period = new DatePeriod(
								new DateTime(date('Y-m-d', strtotime($record['date_start']))),
								new DateInterval('P1D'),
								new DateTime(date('Y-m-d', strtotime($record['date_end'] . ' +1 day')))
							);

							foreach ($period as $day_number => $value) {
								$disabled_dates[] = $value->format('Y-m-d');
							}
						}
					}
				}
			}

			if (isset($wpk_start_dates)) {
				error_log(json_encode($wpk_start_dates));
				error_log(json_encode($wpk_end_dates));
		?>
				<script>
					var wpkStartDates = <?php echo json_encode($wpk_start_dates); ?>;
					var wpkEndDates = <?php echo json_encode($wpk_end_dates); ?>;
				</script>
			<?php
			}
			if (isset($disabled_dates)) {
			?>
				<script>
					var disabledDates = <?php echo json_encode($disabled_dates); ?>;
				</script>
			<?php
			}
		} // end if rental/service


		if ($post_meta['_listing_type'][0] == 'event') {
			$max_tickets = (int) get_post_meta($post_info->ID, "_event_tickets", true);
			$sold_tickets = (int) get_post_meta($post_info->ID, "_event_tickets_sold", true);
			$av_tickets = $max_tickets - $sold_tickets;

			$event_date = get_post_meta($post_info->ID, "_event_date_timestamp", true);
			//			$event_date = strtotime($event_date);
			$current_date = strtotime(date('Y-m-d') . ' +1 day');
			if ($event_date < $current_date) {
			?>
				<p id="sold-out"><?php esc_html_e('The event has passed', 'listeo_core') ?></p>
				</div>
			<?php
				$content = ob_get_clean();
				echo $content;
				return;
			}
			if ($av_tickets <= 0) { ?>
				<p id="sold-out"><?php esc_html_e('The tickets have sold out', 'listeo_core') ?></p>
				</div>
		<?php
				$content = ob_get_clean();
				echo $content;
				return;
			}
		}
		?>

		<div class="row with-forms margin-top-0" id="booking-widget-anchor">
			<form ​ autocomplete="off" id="form-booking" data-post_id="<?php echo $post_info->ID; ?>" class="form-booking-<?php echo $post_meta['_listing_type'][0]; ?>" action="<?php echo esc_url(get_permalink(get_option('listeo_booking_confirmation_page'))); ?>" method="post">

				<?php if ($post_meta['_listing_type'][0] != 'event') {
					$minspan = get_post_meta($post_info->ID, '_min_days', true);
					//WP Kraken
					// If minimub booking days are not set, set to 2 by default
					if (!$minspan && $post_meta['_listing_type'][0] == 'rental') {
						$minspan = 2;
					}
					$minspan = apply_filters('listeo_core_min_booking_days', $minspan, $post_info->ID);
				?>
					<!-- Date Range Picker - docs: http://www.daterangepicker.com/ -->
					<div class="col-lg-12">
						<input type="text" data-minspan="<?php echo ($minspan) ? $minspan : '0'; ?>" id="date-picker" readonly="readonly" class="date-picker-listing-<?php echo esc_attr($post_meta['_listing_type'][0]); ?>" autocomplete="off" placeholder="<?php esc_attr_e('Date', 'listeo_core'); ?>" value="" data-listing_type="<?php echo $post_meta['_listing_type'][0]; ?>" />
					</div>
								
					<!-- Panel Dropdown -->
					<?php if ($post_meta['_listing_type'][0] == 'service' &&   is_array($slots)) {
					$slot_days_array = array();
					foreach ($slots as $day => $day_slots) {
						if (empty($day_slots)) continue; 
						// pon wt srod czwartek piatek sobota niedzial
						// 0   1   2   3         4      5      6
						// 1   2   3   4         5      6      0
						$day++;
						if($day == 7 ){
							$day = 0;
						}
						
						$slot_days_array[] = $day;

					}
					?>
						<div class="col-lg-12">
							<div class="panel-dropdown time-slots-dropdown" data-slots-days=<?php echo implode(',',$slot_days_array); ?>>
								<a href="#" placeholder="<?php esc_html_e('Time Slots', 'listeo_core') ?>"><?php esc_html_e('Time Slots', 'listeo_core') ?></a>

								<div class="panel-dropdown-content timeslot-panel padding-reset">
									<div class="no-slots-information"><?php esc_html_e('No slots for this day', 'listeo_core') ?></div>
									<div class="panel-dropdown-scrollable">
										<input id="slot" type="hidden" name="slot" value="" />
										<input id="listing_id" type="hidden" name="listing_id" value="<?php echo $post_info->ID; ?>" />
										<?php foreach ($slots as $day => $day_slots) {
											if (empty($day_slots)) continue;
										?>

											<?php foreach ($day_slots as $number => $slot) {
												$slot = explode('|', $slot); ?>
												<!-- Time Slot -->
												<div class="time-slot" day="<?php echo $day; ?>">
													<input type="radio" name="time-slot" id="<?php echo $day . '|' . $number; ?>" value="<?php echo $day . '|' . $number; ?>">
													<label for="<?php echo $day . '|' . $number; ?>">
														<p class="day"><?php echo $days_list[$day]; ?></p>
														<strong><?php echo $slot[0]; ?></strong>
														<span><?php echo $slot[1];
																esc_html_e(' slots available', 'listeo_core') ?></span>
													</label>
												</div>
											<?php } ?>

										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					<?php } else if ($post_meta['_listing_type'][0] == 'service') { 
							$time_value = '';
					?>
						<div class="col-lg-12 listeo-service-timepicker">
							<input type="text" class="time-picker flatpickr-input active" placeholder="<?php esc_html_e('Time', 'listeo_core') ?>" id="_hour" name="_hour" readonly="readonly">
						</div>
						<?php if (get_post_meta($post_id, '_end_hour', true)) : ?>
							<div class="col-lg-12">
								<input type="text" class="time-picker time-picker-end-hour flatpickr-input active" placeholder="<?php esc_html_e('End Time', 'listeo_core') ?>" id="_hour_end" name="_hour_end" readonly="readonly">
							</div>
						<?php
						endif;
						$_opening_hours_status = get_post_meta($post_id, '_opening_hours_status', true);
						$_opening_hours_status = '';
						?>
						<script>
							var availableDays = <?php if ($_opening_hours_status) {
													echo json_encode($opening_hours, true);
												} else {
													echo json_encode('', true);
												} ?>;
						</script>

					<?php } ?>

					<?php $bookable_services = listeo_get_bookable_services($post_info->ID);

					if (!empty($bookable_services)) : ?>

						<!-- Panel Dropdown -->
						<div class="col-lg-12">
							<div class="panel-dropdown booking-services">
								<a href="#"><?php esc_html_e('Extra Services', 'listeo_core'); ?> <span class="services-counter">0</span></a>
								<div class="panel-dropdown-content padding-reset">
									<div class="panel-dropdown-scrollable">

										<!-- Bookable Services -->
										<div class="bookable-services">
											<?php
											$i = 0;
											$currency_abbr = get_option('listeo_currency');
											$currency_postion = get_option('listeo_currency_postion');
											$currency_symbol = Listeo_Core_Listing::get_currency_symbol($currency_abbr);
											foreach ($bookable_services as $key => $service) {
												$i++; ?>
												<div class="single-service <?php if (isset($service['bookable_quantity'])) : ?>with-qty-btns<?php endif; ?>">

													<input type="checkbox" autocomplete="off" class="bookable-service-checkbox" name="_service[<?php echo sanitize_title($service['name']); ?>]" value="<?php echo sanitize_title($service['name']); ?>" id="tag<?php echo esc_attr($i); ?>" />

													<label for="tag<?php echo esc_attr($i); ?>">
														<h5><?php echo esc_html($service['name']); ?></h5>
														<span class="single-service-price"> <?php
																							if (empty($service['price']) || $service['price'] == 0) {
																								esc_html_e('Free', 'listeo_core');
																							} else {
																								if ($currency_postion == 'before') {
																									echo $currency_symbol . ' ';
																								}
																								$price = $service['price'];
																								if (is_numeric($price)) {
																									$decimals = get_option('listeo_number_decimals', 2);
																									echo number_format_i18n($price, $decimals);
																								} else {
																									echo esc_html($price);
																								}
																								if ($currency_postion == 'after') {
																									echo ' ' . $currency_symbol;
																								}
																							}
																							?></span>
													</label>

													<?php if (isset($service['bookable_quantity'])) : ?>
														<div class="qtyButtons">
															<input type="text" class="bookable-service-quantity" name="_service_qty[<?php echo sanitize_title($service['name']); ?>]" value="1">
														</div>
													<?php else : ?>
														<input type="hidden" class="bookable-service-quantity" name="_service_qty[<?php echo sanitize_title($service['name']); ?>]" value="1">
													<?php endif; ?>

												</div>
											<?php } ?>
										</div>
										<div class="clearfix"></div>
										<!-- Bookable Services -->


									</div>
								</div>
							</div>
						</div>
						<!-- Panel Dropdown / End -->
					<?php
					endif;
					$max_guests = get_post_meta($post_info->ID, "_max_guests", true);
					$min_guests = get_post_meta($post_info->ID, "_min_guests", true);
					if(empty($min_guests)){
						$min_guests = 1;
					}
					$count_per_guest = get_post_meta($post_info->ID, "_count_per_guest", true);
					if (get_option('listeo_remove_guests')) {
						$max_guests = 1;
					}
					?>
					<!-- Panel Dropdown -->
					<div class="col-lg-12" <?php if ($max_guests == 1) {
												echo 'style="display:none;"';
											} ?>>
						<div class="panel-dropdown">
							<a href="#"><?php esc_html_e('Guests', 'listeo_core') ?> <span class="qtyTotal" name="qtyTotal">1</span></a>
							<div class="panel-dropdown-content" style="width: 269px;">
								<!-- Quantity Buttons -->
								<div class="qtyButtons">
									<div class="qtyTitle"><?php esc_html_e('Guests', 'listeo_core') ?></div>
									<input type="text" name="qtyInput" data-max="<?php echo esc_attr($max_guests); ?>" data-min="<?php echo esc_attr($min_guests); ?>" class="adults <?php if ($count_per_guest) echo 'count_per_guest'; ?>" value="<?php echo $min_guests; ?>">
								</div>

							</div>
						</div>
					</div>
					<!-- Panel Dropdown / End -->

				<?php } //eof !if event 
				?>

				<?php if ($post_meta['_listing_type'][0] == 'event') {
					$max_guests 	= (int) get_post_meta($post_info->ID, "_max_guests", true);
					$max_tickets 	= (int) get_post_meta($post_info->ID, "_event_tickets", true);
					$sold_tickets 	= (int) get_post_meta($post_info->ID, "_event_tickets_sold", true);
					$av_tickets 	= $max_tickets - $sold_tickets;
					if ($av_tickets > $max_guests && $max_guests > 0) {
						$av_tickets = $max_guests;
					}

				?><input type="hidden" id="date-picker" readonly="readonly" class="date-picker-listing-<?php echo esc_attr($post_meta['_listing_type'][0]); ?>" autocomplete="off" placeholder="<?php esc_attr_e('Date', 'listeo_core'); ?>" value="<?php echo $post_meta['_event_date'][0]; ?>" listing_type="<?php echo $post_meta['_listing_type'][0]; ?>" />
					<div class="col-lg-12 tickets-panel-dropdown">
						<div class="panel-dropdown">
							<a href="#"><?php esc_html_e('Tickets', 'listeo_core') ?> <span class="qtyTotal" name="qtyTotal">1</span></a>
							<div class="panel-dropdown-content" style="width: 269px;">
								<!-- Quantity Buttons -->
								<div class="qtyButtons">
									<div class="qtyTitle"><?php esc_html_e('Tickets', 'listeo_core') ?></div>
									<input type="text" name="qtyInput" <?php if ($max_tickets > 0) { ?>data-max="<?php echo esc_attr($av_tickets); ?>" <?php } ?> id="tickets" value="1">
								</div>

							</div>
						</div>
					</div>
					<?php $bookable_services = listeo_get_bookable_services($post_info->ID);

					if (!empty($bookable_services)) : ?>

						<!-- Panel Dropdown -->
						<div class="col-lg-12">
							<div class="panel-dropdown booking-services">
								<a href="#"><?php esc_html_e('Extra Services', 'listeo_core'); ?> <span class="services-counter">0</span></a>
								<div class="panel-dropdown-content padding-reset">
									<div class="panel-dropdown-scrollable">

										<!-- Bookable Services -->
										<div class="bookable-services">
											<?php
											$i = 0;
											$currency_abbr = get_option('listeo_currency');
											$currency_postion = get_option('listeo_currency_postion');
											$currency_symbol = Listeo_Core_Listing::get_currency_symbol($currency_abbr);
											foreach ($bookable_services as $key => $service) {
												$i++; ?>
												<div class="single-service">
													<input type="checkbox" class="bookable-service-checkbox" name="_service[<?php echo sanitize_title($service['name']); ?>]" value="<?php echo sanitize_title($service['name']); ?>" id="tag<?php echo esc_attr($i); ?>" />

													<label for="tag<?php echo esc_attr($i); ?>">
														<h5><?php echo esc_html($service['name']); ?></h5>
														<span class="single-service-price"> <?php
																							if (empty($service['price']) || $service['price'] == 0) {
																								esc_html_e('Free', 'listeo_core');
																							} else {
																								if ($currency_postion == 'before') {
																									echo $currency_symbol . ' ';
																								}
																								echo esc_html($service['price']);
																								if ($currency_postion == 'after') {
																									echo ' ' . $currency_symbol;
																								}
																							}
																							?></span>
													</label>

													<?php if (isset($service['bookable_quantity'])) : ?>
														<div class="qtyButtons">
															<input type="text" class="bookable-service-quantity" name="_service_qty[<?php echo sanitize_title($service['name']); ?>]" data-max="" class="" value="1">
														</div>
													<?php else : ?>
														<input type="hidden" class="bookable-service-quantity" name="_service_qty[<?php echo sanitize_title($service['name']); ?>]" data-max="" class="" value="1">
													<?php endif; ?>
												</div>
											<?php } ?>
										</div>
										<div class="clearfix"></div>
										<!-- Bookable Services -->


									</div>
								</div>
							</div>
						</div>
						<!-- Panel Dropdown / End -->
					<?php
					endif; ?>
					<!-- Panel Dropdown / End -->
				<?php } ?>

				<?php if (!get_option('listeo_remove_coupons')) : ?>
					<div class="col-lg-12 coupon-widget-wrapper">
						<a id="listeo-coupon-link" href="#"><?php esc_html_e('Have a coupon?', 'listeo_core'); ?></a>
						<div class="coupon-form">

							<input type="text" name="apply_new_coupon" class="input-text" id="apply_new_coupon" value="" placeholder="<?php esc_html_e('Coupon code', 'listeo_core'); ?>">
							<a href="#" class="button listeo-booking-widget-apply_new_coupon">
								<div class="loadingspinner"></div><span class="apply-coupon-text"><?php esc_html_e('Apply', 'listeo_core'); ?></span>
							</a>

						</div>
						<div id="coupon-widget-wrapper-output">
							<div class="notification error closeable"></div>
							<div class="notification success closeable" id="coupon_added"><?php esc_html_e('This coupon was added', 'listeo_core'); ?></div>
						</div>
						<div id="coupon-widget-wrapper-applied-coupons">

						</div>
					</div>

					<input type="hidden" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_html_e('Coupon code', 'listeo_core'); ?>">
				<?php endif; ?>
		</div>

		<!-- Book Now -->
		<input type="hidden" id="listing_type" value="<?php echo $post_meta['_listing_type'][0]; ?>" />
		<input type="hidden" id="listing_id" value="<?php echo $post_info->ID; ?>" />
		<input id="booking" type="hidden" name="value" value="booking_form" />
		<?php if (is_user_logged_in()) :

			if ($post_meta['_listing_type'][0] == 'event') {
				$book_btn = esc_html__('Make a Reservation', 'listeo_core');
			} else {
				if (get_post_meta($post_info->ID, '_instant_booking', true)) {
					$book_btn = esc_html__('Book Now', 'listeo_core');
				} else {
					$book_btn = esc_html__('Request Booking', 'listeo_core');
				}
			}

		//	$post_id = $queried_object->ID;
			$author_id = get_post_field('post_author', $post_id);
			$current_user = wp_get_current_user();
			$user_id = get_current_user_id();
			$roles = $current_user->roles;
			$role = array_shift($roles);
			if (get_option('listeo_owners_can_book') != 'on' && in_array($role, array('owner', 'seller'))) { ?>
				<a href="#" class="button fullwidth white margin-top-5"><span class="book-now-text"><?php echo esc_html__("Please use guest account.", 'listeo_core');  ?></span></a>
			<?php } else {  ?>
				<a href="#" class="button book-now fullwidth margin-top-5">
					<div class="loadingspinner"></div><span class="book-now-text"><?php echo $book_btn; ?></span>
				</a>

			<?php } ?>




			<?php else :
			$popup_login = get_option('listeo_popup_login', 'ajax');
			if ($popup_login == 'ajax') { ?>

				<a href="#sign-in-dialog" class="button fullwidth margin-top-5 popup-with-zoom-anim book-now-notloggedin">
					<div class="loadingspinner"></div><span class="book-now-text"><?php esc_html_e('Login to Book', 'listeo_core') ?></span>
				</a>

			<?php } else {

				$login_page = get_option('listeo_profile_page'); ?>
				<a href="<?php echo esc_url(get_permalink($login_page)); ?>" class="button fullwidth margin-top-5 book-now-notloggedin">
					<div class="loadingspinner"></div><span class="book-now-text"><?php esc_html_e('Login To Book', 'listeo_core') ?></span>
				</a>
			<?php } ?>

		<?php endif; ?>

		<?php if ($post_meta['_listing_type'][0] == 'event' && isset($post_meta['_event_date'][0])) { ?>
			<div class="booking-event-date">
				<strong><?php esc_html_e('Event date', 'listeo_core'); ?></strong>
				<span><?php

						$_event_datetime = $post_meta['_event_date'][0];
						$_event_date = list($_event_datetime) = explode(' -', $_event_datetime);

						echo $_event_date[0]; ?></span>
			</div>
		<?php } ?>

		<?php
		$currency_abbr = get_option('listeo_currency');
		$currency_postion = get_option('listeo_currency_postion');
		$currency_symbol = Listeo_Core_Listing::get_currency_symbol($currency_abbr, false);
		?>
		<div class="booking-estimated-cost" <?php if ($post_meta['_listing_type'][0] != 'event') { ?>style="display: none;" <?php } ?>>
			<?php if ($post_meta['_listing_type'][0] == 'event') {
				$reservation_fee = (float) get_post_meta($post_info->ID, '_reservation_price', true);
				$normal_price = (float) get_post_meta($post_info->ID, '_normal_price', true);

				$event_default_price = $reservation_fee + $normal_price;
			}  ?>
			<strong><?php esc_html_e('Total Cost', 'listeo_core'); ?></strong>
			<span data-price="<?php if (isset($event_default_price)) {
									echo esc_attr($event_default_price);
								} ?>">
				<?php if ($currency_postion == 'before') {
					echo $currency_symbol;
				} ?>
				<?php
				if ($post_meta['_listing_type'][0] == 'event') {

					echo $event_default_price;
				} else echo '0'; ?>
				<?php if ($currency_postion == 'after') {
					echo $currency_symbol;
				} ?>
			</span>
		</div>

		<div class="booking-estimated-discount-cost" style="display: none;">

			<strong><?php esc_html_e('Final Cost', 'listeo_core'); ?></strong>
			<span>
				<?php if ($currency_postion == 'before') {
					echo $currency_symbol;
				} ?>

				<?php if ($currency_postion == 'after') {
					echo $currency_symbol;
				} ?>
			</span>
		</div>
		<div class="booking-error-message" style="display: none;">
			<?php if ($post_meta['_listing_type'][0] == 'subservice' && !$slots) {
				esc_html_e('Unfortunately we are closed at selected hours. Try different please.', 'listeo_core');
			} else {
				esc_html_e('Unfortunately this request can\'t be processed. Try different dates please.', 'listeo_core');
			} ?>
		</div>
		</form>

        </div>
		</div>
<?php


		$content = ob_get_clean();
		echo $content;


?>
