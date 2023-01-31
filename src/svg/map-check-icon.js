export const MapCheckIcon = ({ count }) => {
	return <p className="mapCheckText">
		<img
			className="mapCheckIconStyling"
			alt={`${count} Affiliates`}
			src="http://localhost/cpd/wp-content/uploads/2022/12/map-check-icon_white.png"
		/>
		&nbsp; {count} Affiliates
	</p>;
};
