/*
    AES Paid Meeting Form Functions
    Author: MVC <michaelc@drinkcaffeine.com>
*/
jQuery.noConflict();
(function($) {
  $(function() {

        // Get the original dropdowns from build
        var $start = $('#edit-meeting-start option');
        var $end = $('#edit-meeting-end option');

        /*
        Days & Hours
            The first array entry is blank
            The next ones are the corresponding days of the week

                Each day has multiple arrays which correspond to the times
                in the original dropdowns as built

                The multiple arrays define the limits, for example:

                If Thursday Dec 3rd has 7am-noon, noon-2pm, and 8pm-11pm then it
                would have 3 arrays with the corresponding integers (see bottom):

                    [29,49]
                    [49,57]
                    [81,93]
        */
        var date_times = [
            [],
            [
                [29,89]
            ],
            [
                [29,93]
            ],
            [
                [35,61],
                [75,93]
            ],
            [
                [29,35],
                [81,93]
            ],
            [
                [73,93]
            ],
            [
                [29,35],
                [85,93]
            ],
            [
                [29,35],
                [44,69]
            ]
        ];

        // Alter meeting start list
        function aes_meeting_day()
        {

            var selected = 0;

            // Get rid of all then add original
            $( '#edit-meeting-start option' ).remove();
            $( '#edit-meeting-start' ).append( $start.eq( 0 ) );

            // If a day was chosen, put the visible items back
            if( $( '#edit-meeting-day' ).val() )
            {
                // Loop through the days
                day_times = date_times[$( '#edit-meeting-day' ).val()];
                for( n = 0; n < day_times.length; n++ )
                {
                    // Loop through the times of day
                    o = ( n < ( day_times.length - 1 ) ) ? 1 : 0;
                    for( v = day_times[n][0]; v < ( day_times[n][1] + o ); v++ )
                    {
                        // Add this time in
                        $( '#edit-meeting-start' ).append( $start.eq( v ) );
                        if( $start.eq( v ).attr( 'selected' ) == 'selected' ) selected = v;
                    }
                }
            }

            // Reset the values
            $( '#edit-meeting-start' ).val( 0 );
            $( '#edit-meeting-end' ).val( 0 );

            // Check cost
            aes_meeting_calculation();

        }
        // Run on startup
        aes_meeting_day();

        // Alter meeting end list
        function aes_meeting_start()
        {

            // Get rid of all then add original
            $( '#edit-meeting-end option' ).remove();
            $( '#edit-meeting-end' ).append( $end.eq( 0 ) );

            // If a start time was chosen, put the visible items back
            if( $( '#edit-meeting-start' ).val() )
            {
                // Get the range.
                var s = $( '#edit-meeting-start' ).val();
                var day_times = date_times[$( '#edit-meeting-day' ).val()];
                var early = 0;
                var late = 0;
                // Loop through the times
                for( n = 0; n < day_times.length; n++ )
                {
                    early = day_times[n][0];
                    late = day_times[n][1];
                    if( s >= early && s < late ) {
                        begin = ( early + 1 );
                        end = late;
                        break;
                    }
                }
                // Implement the range
                for( v = begin; v < ( end + 1 ); v++ )
                {
                    if( v > s ) $( '#edit-meeting-end' ).append( $end.eq( v ) );
                }
            }

            // Reset the values
            $('#edit-meeting-end').val(0);

            // Check cost
            aes_meeting_calculation();

        }
        // Run on startup
        aes_meeting_start();

        // Cost calculation
        // TODO: Drupal variables passed to JS for amounts?
        function aes_meeting_calculation()
        {

            // Turn chosen times into integers
            var time_start = parseInt( $( '#edit-meeting-start' ).val() );
            var time_end = parseInt( $( '#edit-meeting-end' ).val() );

            if( time_end > 0 )
            {
                // Handle valid times
                var time_diff = ( time_end - time_start );
                var time_minutes = ( time_diff * 15 );
                $( '#meeting-time-calc' ).text( time_minutes );
                if( time_minutes <= 90 ) $( '#meeting-cost-display' ).text( '250.00' );
                if( time_minutes > 90 && time_minutes <= 240 ) $( '#meeting-cost-display' ).text( '400.00' );
                if( time_minutes > 240 ) $( '#meeting-cost-display ').text( '500.00' );
            }
            else
            {
                // What sort of trickery is this
                $( '#meeting-time-calc' ).text( '??' );
                $( '#meeting-cost-display' ).text( '??.??' );
            }

        }

        // Triggers
        $( '#edit-meeting-day' ).change( function() { aes_meeting_day(); } );
        $( '#edit-meeting-start' ).change( function() { aes_meeting_start(); } );
        $( '#edit-meeting-end' ).change( function() { aes_meeting_calculation(); } );

  });
})(jQuery);

/*
    Original Select List
    This is the select list for the time ranges, so the integer used by the arrays
    can match up.

    <option value="1">12:00 am</option>
    <option value="2">12:15 am</option>
    <option value="3">12:30 am</option>
    <option value="4">12:45 am</option>
    <option value="5">1:00 am</option>
    <option value="6">1:15 am</option>
    <option value="7">1:30 am</option>
    <option value="8">1:45 am</option>
    <option value="9">2:00 am</option>
    <option value="10">2:15 am</option>
    <option value="11">2:30 am</option>
    <option value="12">2:45 am</option>
    <option value="13">3:00 am</option>
    <option value="14">3:15 am</option>
    <option value="15">3:30 am</option>
    <option value="16">3:45 am</option>
    <option value="17">4:00 am</option>
    <option value="18">4:15 am</option>
    <option value="19">4:30 am</option>
    <option value="20">4:45 am</option>
    <option value="21">5:00 am</option>
    <option value="22">5:15 am</option>
    <option value="23">5:30 am</option>
    <option value="24">5:45 am</option>
    <option value="25">6:00 am</option>
    <option value="26">6:15 am</option>
    <option value="27">6:30 am</option>
    <option value="28">6:45 am</option>
    <option value="29">7:00 am</option>
    <option value="30">7:15 am</option>
    <option value="31">7:30 am</option>
    <option value="32">7:45 am</option>
    <option value="33">8:00 am</option>
    <option value="34">8:15 am</option>
    <option value="35">8:30 am</option>
    <option value="36">8:45 am</option>
    <option value="37">9:00 am</option>
    <option value="38">9:15 am</option>
    <option value="39">9:30 am</option>
    <option value="40">9:45 am</option>
    <option value="41">10:00 am</option>
    <option value="42">10:15 am</option>
    <option value="43">10:30 am</option>
    <option value="44">10:45 am</option>
    <option value="45">11:00 am</option>
    <option value="46">11:15 am</option>
    <option value="47">11:30 am</option>
    <option value="48">11:45 am</option>
    <option value="49">12:00 pm</option>
    <option value="50">12:15 pm</option>
    <option value="51">12:30 pm</option>
    <option value="52">12:45 pm</option>
    <option value="53">1:00 pm</option>
    <option value="54">1:15 pm</option>
    <option value="55">1:30 pm</option>
    <option value="56">1:45 pm</option>
    <option value="57">2:00 pm</option>
    <option value="58">2:15 pm</option>
    <option value="59">2:30 pm</option>
    <option value="60">2:45 pm</option>
    <option value="61">3:00 pm</option>
    <option value="62">3:15 pm</option>
    <option value="63">3:30 pm</option>
    <option value="64">3:45 pm</option>
    <option value="65">4:00 pm</option>
    <option value="66">4:15 pm</option>
    <option value="67">4:30 pm</option>
    <option value="68">4:45 pm</option>
    <option value="69">5:00 pm</option>
    <option value="70">5:15 pm</option>
    <option value="71">5:30 pm</option>
    <option value="72">5:45 pm</option>
    <option value="73">6:00 pm</option>
    <option value="74">6:15 pm</option>
    <option value="75">6:30 pm</option>
    <option value="76">6:45 pm</option>
    <option value="77">7:00 pm</option>
    <option value="78">7:15 pm</option>
    <option value="79">7:30 pm</option>
    <option value="80">7:45 pm</option>
    <option value="81">8:00 pm</option>
    <option value="82">8:15 pm</option>
    <option value="83">8:30 pm</option>
    <option value="84">8:45 pm</option>
    <option value="85">9:00 pm</option>
    <option value="86">9:15 pm</option>
    <option value="87">9:30 pm</option>
    <option value="88">9:45 pm</option>
    <option value="89">10:00 pm</option>
    <option value="90">10:15 pm</option>
    <option value="91">10:30 pm</option>
    <option value="92">10:45 pm</option>
    <option value="93">11:00 pm</option>
    <option value="94">11:15 pm</option>
    <option value="95">11:30 pm</option>
    <option value="96">11:45 pm</option>

*/
