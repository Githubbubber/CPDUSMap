import defaultStateData from "./states-data.json";
import eastCoastBBox from "./east-coast-boundary-box-data.json";
import westCoastBBox from "./west-coast-boundary-box-data.json";
import midWestCentralBBox from "./central-boundary-box-data.json";
import nationWideBBox from "./nation-wide-box-data.json";

const westNWSWPacific = ["wa", "or", "ca", "ak", "hi", "nv", "id", "az", "ut", "nm", "co", "mt", "wy"]; // 13
const midWest = ["il", "ia", "ks", "mn", "mo", "ne", "nd", "sd", "wi"];// 9
const eastSouthPR = ["al", "in", "ct", "de", "ar", "fl", "ga", "ky", "la", "ms", "nc", "ok", "sc", "tn", "tx", "va", "wv", "pr", "me", "md", "ma", "mi", "nh", "nj", "ny", "oh", "pa", "ri", "vt", "dc"]; // 30

/**
 * Gather all state data into one object.
 * First, we will determine what view to show the user: nation-wide or region-specific.
 * Let's find the user's region and zoom in that view. But if the 
 * user's screen is larger than 768px, we will show the nation-wide view.
 * 
 * Then we will append the rest of the data to this object.
 * 
 * @returns {Object} The data for the map.
 */
const getData = (screenWidth, stateAbbr) => {
    let visitorRegionBBox = westNWSWPacific.includes(stateAbbr) ?
        westCoastBBox :
        midWest.includes(stateAbbr) ?
            midWestCentralBBox :
            eastSouthPR.includes(stateAbbr) ?
                eastCoastBBox : nationWideBBox;

    visitorRegionBBox = screenWidth > 768 ? nationWideBBox : visitorRegionBBox;

    return {
        ...visitorRegionBBox,
        ...defaultStateData,
    };
};

export default getData;
