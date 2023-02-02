export const MapCheckIcon = ({ count }) => {
	console.log(wp.media, "wp.media");

	return <p className="mapCheckText">
		<img
			className="mapCheckIconStyling"
			alt={`${count} Affiliates`}
			src={main_site_url + "/wp-content/uploads/map-check-icon_white.png"}
		/>
		&nbsp; {count} Affiliates
	</p>;
};
