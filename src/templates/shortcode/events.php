<table class="osm-public-table">
    <thead>
        <tr>
            <th>Start Date/Time</th>
            <th>End Date/Time</th>
            <th>Name</th>
            <th>Location</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $events as $event ): ?>
            <tr>
                <td style="white-space: nowrap;"><?php echo esc_html( DateTime::createFromFormat('d/m/Y', $event['startdate'])->format('d M Y') ) . ' ' . esc_html( date( 'H:i:s', strtotime( $event['starttime'] ) ) ); ?></td>
                <td style="white-space: nowrap;"><?php echo esc_html( DateTime::createFromFormat('d/m/Y', $event['enddate'])->format('d M Y') ) . ' ' . esc_html( date( 'H:i:s', strtotime( $event['endtime'] ) ) ); ?></td>
                <td style="white-space: nowrap;"><?php echo esc_html( $event['name'] ); ?></td>
                <td><?php echo esc_html( $event['location'] ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>