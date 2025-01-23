<table class="osm-public-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Title</th>
            <th>Summary</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $programme as $item ): ?>
            <tr>
                <td style="white-space: nowrap;"><?php echo esc_html( date( $date_format, strtotime( $item['meetingdate'] ) ) ); ?></td>
                <td style="white-space: nowrap;"><?php echo esc_html( $item['title'] ); ?></td>
                <td><?php echo esc_html( $item['notesforparents'] ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>