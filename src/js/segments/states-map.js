import React, { Component, createRef } from "react";
import * as d3 from 'd3';
import { feature, mesh } from "topojson-client";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCircleChevronLeft, faCircleChevronRight } from "@fortawesome/free-solid-svg-icons";

import getData from "../data/all-data.js";
import puertoRicoData from "../data/puerto-rico-data.json";
import { PuertoRicoDValue, PuertoRicoDValueLG } from "../paths/puerto-rico";
import * as styles from "../../css/editor.scss";

/**
 * @returns {Node} StatesMap
 */
export default class StatesMap extends Component {
    constructor(props) {
        super(props);

        this.state = {
            showAside: false,
            mapOfChosenAffPerState: new Map(),
            selectedState: null,
            wobble: 0,
        };

        this.pContainerRef = createRef();
        this.asideCloseRef = createRef();
        this.pContactInfoRef = createRef();
        this.asideAffiliatesRef = createRef();
        this.mapIconContainerRef = createRef();

        this.getAffiliatePageNumber = this.getAffiliatePageNumber.bind(this);
        this.setNewAffiliateContents = this.setNewAffiliateContents.bind(this);
        this.handlePrevAffiliate = this.handlePrevAffiliate.bind(this);
        this.handleNextAffiliate = this.handleNextAffiliate.bind(this);
        this.asideStuff = this.asideStuff.bind(this);

        this.screenWidth = window.innerWidth;
        this.allAffiliates = cpdusmap_values.allAffiliates;
        this.pCountInCurrentState = parseInt(cpdusmap_values.pCountInCurrentState);
        this.totalPCountsByState = cpdusmap_values.totalPCountsByState;
        this.visitorStateAbbr = cpdusmap_values.currentStateArray.abbr;
        this.visitorStateName = cpdusmap_values.currentStateArray.state;

        this.width = this.screenWidth < 651 ? 550 : 930;
        this.height = this.screenWidth < 651 ? 550 : 630;

        this.nonAffiliates = ["al", "hi", "id", "il", "ia", "ky", "ms", "ne", "nd", "ok", "ri", "sc", "sd", "tn", "ut", "wy"];
        this.stateFillColors = ["#ffb423", "#000000", "#ff5200", "#ff9800", "#ffbe00", "#091c46", "#5e0001", "#ea3c78", "#eb383b", "#49b8f9"];
        this.numbers = ["one", "two", "three", "four", "five", "six", "seven", "eight", "nine"];
    }

    getAffiliatePageNumber(selectedState, currentStateAffiliatesCount, direction = null) {
        const { mapOfChosenAffPerState } = this.state;
        const formerPNum = mapOfChosenAffPerState.get(selectedState);

        let currentPNumToLoad;

        if (direction && direction === "left") {
            currentPNumToLoad = formerPNum - 1 >= 0 ? formerPNum - 1 : currentStateAffiliatesCount - 1;
        } else {
            currentPNumToLoad = formerPNum + 1 < currentStateAffiliatesCount ? formerPNum + 1 : 0;
        }

        let newMapOfNumbers = mapOfChosenAffPerState;
        newMapOfNumbers.set(selectedState, currentPNumToLoad);

        this.setState({ mapOfChosenAffPerState: newMapOfNumbers });

        return currentPNumToLoad;
    };

    setNewAffiliateContents(selectedState, currentAffiliateInfo) {
        const { mapOfChosenAffPerState } = this.state;
        const pageNumber = mapOfChosenAffPerState.get(selectedState);
        const pContactInfoRef = this.pContactInfoRef.current;

        const affiliateCont = d3.create("div")
            .attr("class", `affiliate-${pageNumber}`);

        affiliateCont.append("p")
            .attr("class", "affiliate-name")
            .attr("style", "font-size: 1.5rem; font-weight: 700; margin-left: -6px;")
            .append("a")
            .attr("href", currentAffiliateInfo.site_url)
            .text(currentAffiliateInfo.name);

        affiliateCont.append("p")
            .attr("class", "address")
            .html(currentAffiliateInfo.addr1 + "<br />" +
                currentAffiliateInfo.city + ", " +
                currentAffiliateInfo.state + " " +
                currentAffiliateInfo.zipcode);

        affiliateCont.append("p")
            .attr("class", "phone")
            .html("Tel: " + currentAffiliateInfo.phone);

        affiliateCont.append("p")
            .attr("class", "tagline")
            .append("em")
            .text(currentAffiliateInfo.tagline);

        pContactInfoRef.append(affiliateCont.node());
        return;
    };

    handlePrevAffiliate() {
        const { allAffiliates } = this;
        const { selectedState } = this.state;
        const pContactInfoRef = this.pContactInfoRef.current;
        const currentStateAffiliates = allAffiliates[selectedState];
        const currentPNum = this.getAffiliatePageNumber(selectedState, currentStateAffiliates.length, "left");
        const currentAffiliateInfo = currentStateAffiliates[currentPNum];

        // Fade former affiliate out
        pContactInfoRef.animate([
            {
                transform: 'translateX(0)',
                opacity: 1
            },
            {
                transform: 'translateX(100%)',
                opacity: 0
            }
        ], {
            duration: 150,
            iterations: 1
        });

        // Gut affiliate container
        while (pContactInfoRef.lastElementChild) {
            pContactInfoRef.removeChild(pContactInfoRef.lastElementChild);
        }

        // Build affiliate container out with new affiliate
        this.setNewAffiliateContents(selectedState, currentAffiliateInfo);

        // Fade new affiliate in
        pContactInfoRef.animate([
            {
                transform: 'translateX(-100%)',
                opacity: 0
            },
            {
                transform: 'translateX(0)',
                opacity: 1
            }
        ], {
            duration: 150,
            iterations: 1
        });

        pContactInfoRef.setAttribute("class", `affiliate-${currentPNum}`);
    };

    handleNextAffiliate() {
        const { allAffiliates } = this;
        const { selectedState } = this.state;
        const pContactInfoRef = this.pContactInfoRef.current;
        const currentStateAffiliates = allAffiliates[selectedState];
        const currentPNum = this.getAffiliatePageNumber(selectedState, currentStateAffiliates.length);
        const currentAffiliateInfo = allAffiliates[selectedState][currentPNum];

        const newspaperSpinning = [
            { transform: 'rotate(0) scale(1)' },
            { transform: 'rotate(360deg) scale(0)' }
        ];

        const newspaperTiming = {
            duration: 2000,
            iterations: 1,
        };

        pContactInfoRef.animate(newspaperSpinning, newspaperTiming);
    };

    asideStuff(selectedState) {
        const { allAffiliates } = this;
        const { mapOfChosenAffPerState } = this.state;
        const pageNumber = mapOfChosenAffPerState.get(selectedState);
        const asideAffiliatesRef = this.asideAffiliatesRef.current;

        this.setNewAffiliateContents(selectedState, allAffiliates[selectedState][pageNumber]);
    }

    componentDidMount() {
        const { pContainerRef } = this;

        let cpdZoom, cpdSVG, cpdG, cpdStates, cpdAddPuertoRico, statesData;
        let path = d3.geoPath();

        statesData = getData(this.screenWidth, this.visitorStateAbbr);

        const zoomed = (event) => {
            const { transform } = event;

            cpdG.attr("transform", transform);
            cpdG.attr("stroke-width", 1 / transform.k);
        };

        const reset = () => {
            cpdStates.transition().style("fill", null);

            cpdSVG.transition().duration(750).call(
                cpdZoom.transform,
                d3.zoomIdentity,
                d3.zoomTransform(cpdSVG.node()).invert([this.width / 2, this.height / 2])
            );

            const prElG = document.querySelector(".prG");
            const prEl = document.querySelector(".pr");

            d3.select(prElG).attr("transform", null);
            d3.select(prElG).attr("xmlns", null);

            d3.select(prEl).transition().attr("d", PuertoRicoDValue);

            d3.select(prElG).attr("style", "display: block;");
        };

        const clicked = (event, d) => {
            event.stopPropagation();

            let classValue = event.target.getAttribute("class");
            const selectedState = event.target.getAttribute("data-state");

            if (classValue && !this.nonAffiliates.includes(classValue)) {
                const [[x0, y0], [x1, y1]] = path.bounds(d);

                cpdStates.transition().style("fill", null);

                d3.select(event.target).transition().style("fill", "#ff8200");

                cpdSVG.transition().duration(750).call(
                    cpdZoom.transform,
                    d3.zoomIdentity
                        .translate(this.width / 2, this.height / 2)
                        .scale(Math.min(8, 0.9 / Math.max((x1 - x0) / this.width, (y1 - y0) / this.height)))
                        .translate(-(x0 + x1) / 2, -(y0 + y1) / 2),
                    d3.pointer(event, cpdSVG.node())
                );

                const prElG = document.querySelector(".prG");

                d3.select(prElG).attr("style", "display: none;");
                d3.select(prElG).attr("class", "hidePagesContainer");

                this.setState({ showAside: true, selectedState: selectedState });

                this.asideStuff(selectedState);
            } else {
                reset();

                this.setState({ showAside: false, selectedState: null });
            }
        };

        const clickedPR = (event) => {
            event.stopPropagation();

            cpdStates.transition().style("fill", null);

            d3.select(event.target).transition().style("fill", "#ff8200");

            cpdSVG.transition().duration(750).call(
                cpdZoom.transform,
                d3.zoomIdentity
                    .translate(-this.width / 7, -this.height / 6)
                    .scale(1.4)
                    .translate(-155.0482880470781, -50.2889350834307),
                d3.pointer(event, cpdSVG.node())
            );

            const prElG = document.querySelector(".prG");

            d3.select(prElG).attr("transform", "translate(0.000000,38.000000) scale(0.100000,-0.100000)");
            d3.select(prElG).attr("xmlns", "http://www.w3.org/2000/svg");

            d3.select(event.target).transition().attr("d", PuertoRicoDValueLG);

            this.setState({ showAside: true, selectedState: "Puerto Rico" });

            this.asideStuff("Puerto Rico");
        };

        const sendFillColor = (d) => {
            const randomColor = this.stateFillColors[
                Math.floor(Math.random() *
                    this.stateFillColors.length)
            ];

            if (this.nonAffiliates.includes(d.properties.abbr)) {
                return "#777";
            }

            return randomColor;
        };

        const showCursorInfo = (event) => {
            const classValue = event.target.getAttribute("class");
            const xBounds = this.width - 250;
            const yBounds = this.height - 250;

            if (!this.nonAffiliates.includes(classValue)) {
                const coordValues = d3.pointer(event, cpdSVG.node());

                let xC = Math.abs(Math.round(coordValues[0]));
                xC = xC > xBounds ? xC - 175 : xC;
                let yC = Math.abs(Math.round(coordValues[1]));
                yC = yC > yBounds ? yC - 175 : yC;
                let count = this.totalPCountsByState[classValue];
                count = count > 1 ? `${count} Affiliates` : "1 Affiliate";

                const iconP = d3.create("p")
                    .attr("class", "mapCheckText")
                    .attr("style", `position: absolute; left: ${xC}px; top: ${yC}px;`);

                const iconImg = d3.create("img")
                    .attr("class", "mapCheckIconStyling")
                    .attr("alt", count)
                    .attr("src", site_url + "/wp-content/uploads/2022/12/map-check-icon_white.png"); // TODO: Check

                const iconSpan = d3.create("span")
                    .text(count);

                iconP.append(() => iconImg.node());
                iconP.append(() => iconSpan.node());

                this.mapIconContainerRef.current.innerHTML = "";
                this.mapIconContainerRef.current.append(iconP.node());
            }
        };

        const hideCursorInfo = (event) => {
            let classValue = event.target.getAttribute("class");

            if (!this.nonAffiliates.includes(classValue)) {
                this.mapIconContainerRef.current.innerHTML = "";
            }
        };

        cpdZoom = d3.zoom()
            .scaleExtent([1, 8])
            .on("zoom", zoomed);

        cpdSVG = d3.create("svg")
            .attr("viewBox", [0, 0, this.width, this.height])
            .attr("width", () => {
                if (this.screenWidth < 651) {
                    return this.width;
                } else {
                    return this.width - 100;
                }
            })
            .attr("height", () => {
                if (this.screenWidth < 651) {
                    return this.height;
                } else {
                    return this.height - 100;
                }
            })
            .on("click", () => {
                reset();

                this.setState({ showAside: false, selectedState: null });
            });

        cpdG = cpdSVG.append("g");

        cpdStates = cpdG.append("g")
            .attr("fill", "#777")
            .attr("cursor", "pointer")
            .selectAll("path")
            .data(feature(statesData, statesData.objects.states).features)
            .join("path")
            .attr("class", (d) => d.properties.abbr)
            .attr("fill", sendFillColor)
            .attr("data-state", (d) => d.properties.data)
            .attr("d", path)
            .on("click", clicked)
            .on("mouseover", (e) => {
                if (this.screenWidth > 768) {
                    showCursorInfo(e);
                }
            })
            .on("mouseout", (e) => {
                if (this.screenWidth > 768) {
                    hideCursorInfo(e);
                }
            });

        cpdStates.append("title")
            .text(d => d.properties.name);

        cpdStates.each((d) => {
            const stateName = d.properties.data;
            const stateAbbr = d.properties.abbr;
            const pNumsMap = this.state.mapOfChosenAffPerState;

            if (!this.nonAffiliates.includes(stateAbbr)) {
                pNumsMap.set(stateName, 0);
            }
        });

        cpdG.append("path")
            .attr("fill", "none")
            .attr("stroke", "#fff")
            .attr("stroke-linejoin", "round")
            .attr("d", path(mesh(statesData, statesData.objects.states, (a, b) => a !== b)));

        cpdAddPuertoRico = cpdSVG.append("g")
            .attr("fill", "#777")
            .attr("cursor", "pointer")
            .attr("class", "prG")
            .selectAll("path")
            .data(feature(puertoRicoData, puertoRicoData.objects.states).features)
            .join("path")
            .attr("class", "pr")
            .attr("fill", sendFillColor)
            .attr("d", PuertoRicoDValue)
            .on("click", clickedPR)
            .on("mouseover", showCursorInfo)
            .on("mouseout", hideCursorInfo);

        cpdAddPuertoRico.append("title")
            .text(d => d.properties.name);

        cpdSVG.call(cpdZoom);

        pContainerRef.current.appendChild(cpdSVG.node());
    }

    render() {
        const { showAside } = this.state;
        const convertedCount = this.pCountInCurrentState < 10 ? this.numbers[this.pCountInCurrentState + 1] : this.pCountInCurrentState;
        const tailoredMsg = this.pCountInCurrentState > 0 ? ` (including ${convertedCount} in YOUR state!)` : null;
        const nationalAffiliatesCount = Object.values(this.totalPCountsByState).reduce((a, b) => parseInt(a) + parseInt(b), 0);

        const AsideInfoPanel = () => {
            return <aside className="infoCardContainer">
                <div className="asideAffiliates" ref={this.asideAffiliatesRef}>
                    <div
                        className={styles.divHolder}
                        ref={this.pContactInfoRef}
                        wobble={this.state.wobble}
                    />

                    <FontAwesomeIcon
                        icon={faCircleChevronLeft}
                        className="pageTurnerPrev"
                        onClick={() => { this.setState({ wobble: 0 }); }} />
                    <FontAwesomeIcon
                        icon={faCircleChevronRight}
                        className="pageTurnerNext"
                        onClick={() => { this.setState({ wobble: 1 }); }} />
                </div>
            </aside >;
        };

        const AsideContainer = () => {
            if (!showAside) {
                return <aside className="infoStatementContainer">
                    <p>
                        CPD's network of over {nationalAffiliatesCount} affiliates{tailoredMsg} bring a strong and supportive force for promoting voter rights in the United States.<br />
                        <br />
                        Please click on any of our affiliate states for more info on how to volunteer, and where you can get voting information.
                    </p>
                </aside>;
            } else {
                return <AsideInfoPanel />;
            }
        };

        return <div className="infoAndPsMapRowContainer">
            <div className="mapIconContainer" ref={this.mapIconContainerRef}></div>

            <AsideContainer />

            <figure className='affiliateMapContainer' ref={this.pContainerRef}></figure>
        </div >;
    }
};
