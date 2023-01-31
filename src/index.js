import { getBlockType, registerBlockType } from '@wordpress/blocks';

import Edit from './js/Edit';
import Save from "./js/Save";
import metadata from './block.json';

const CPDUSMapIcon = () => {
    return (
        <svg
            width="20"
            height="20"
            viewBox="100 100 612 612"
            className="dashicon"
            version="1.1"
            xmlns="http://www.w3.org/2000/svg"
            x="0px"
            y="0px"
        >
            <path
                fill="#ff9800"
                d="M220 674 c-190 -36 -186 -37 75 -18 l70 5 -180 -75 c-99 -41 -176 -75 -171 -76 4 0 87 25 183 56 96 30 176 54 178 52 2 -2 -39 -39 -91 -82 -134 -113 -130 -113 33 -4 56 37 105 68 108 68 3 0 -35 -55 -85 -122 l-91 -123 100 105 101 105 -79 -167 c-44 -93 -78 -168 -77 -168 2 0 43 70 92 155 48 85 89 153 92 151 2 -2 -4 -28 -12 -57 -24 -80 -18 -82 13 -5 28 68 28 69 34 36 4 -18 8 -92 9 -164 l1 -131 10 163 c5 89 12 162 16 162 4 0 15 -38 25 -85 10 -47 21 -84 23 -81 2 2 -1 41 -7 86 -6 45 -9 84 -7 86 2 2 41 -68 87 -156 46 -88 85 -158 87 -157 1 2 -29 76 -67 165 -38 90 -72 173 -75 185 -4 13 26 -13 74 -63 l82 -85 -61 93 c-33 50 -58 92 -55 92 4 0 60 -38 126 -85 65 -46 119 -83 119 -80 0 2 -63 58 -139 124 l-140 121 -138 -1 c-99 -1 -173 -8 -263 -25z"
            />
        </svg>
    );
};
const blockName = metadata.name;

if (!getBlockType(blockName)) {
    registerBlockType(blockName, {
        title: 'CPD US Map',
        description: "An interactive, CPD info-tailored map of the United States.",
        icon: CPDUSMapIcon,
        category: 'widgets',
        edit: Edit,
        save: Save,
    });
}
