var PricingSliderHandler = function ($scope, $) {
   function normalizeFilterToken(value) {
      return String(value ?? "")
         .trim()
         .toLowerCase();
   }

   function parseFilterTokens(filterData) {
      return String(filterData ?? "")
         .split(",")
         .map((token) => normalizeFilterToken(token))
         .filter(Boolean);
   }

   class PricingSlider {
      constructor($scope) {
         this.$scope = $scope;
         this.instanceId = $scope.data("id") || $scope.find(".eael-pricing-slider").attr("data-widget-id") || "";
         this.instanceSuffix = this.instanceId ? `-${this.instanceId}` : "";
         this.eventName = "pricingSliderChange";

         this.markersContainer = $scope.find(`#eael-pricing-slider-controls${this.instanceSuffix}`)[0] || $scope.find(".eael-pricing-slider-controls")[0];
         this.handle = $scope.find(`#eael-pricing-slider-handle${this.instanceSuffix}`)[0] || $scope.find(".eael-pricing-slider-handle")[0];
         this.fill = $scope.find(`#eael-pricing-slider-fill${this.instanceSuffix}`)[0] || $scope.find(".eael-pricing-slider-fill")[0];
         this.tooltip = $scope.find(`#eael-pricing-slider-tooltip${this.instanceSuffix}`)[0] || $scope.find(".eael-pricing-slider-tooltip")[0];
         this.sliderBar = $scope.find(`#eael-pricing-slider-bar${this.instanceSuffix}`)[0] || $scope.find(".eael-pricing-slider-bar")[0];
         this.plansContainer = $scope.find(`#plans-container${this.instanceSuffix}`)[0] || $scope.find(".eael-pricing-plans-container")[0];

         // Style Two curve elements
         this.isStyleTwo = $scope.find(".eael-pricing-slider-style-2").length > 0;
         this.curvePath = $scope.find(".eael-pricing-slider-curve-path")[0] || null;
         this.curveSvg = $scope.find(".eael-pricing-slider-curve")[0] || null;

         this.currentPricePoint = 0;
         this.currentPercent = 0;
         this.isDragging = false;

         this.pricePoints = [];
         this.priceLabels = [];
         this.priceTooltipActives = [];
         this.priceTooltipTexts = [];
         this.priceMarkers = [];
         this.activeDefaultIndex = -1;

         this.initialize();
      }

      initialize() {
         this.readPricePointsFromHTML();
         this.bindEvents();

         const initialValue = this.activeDefaultIndex !== -1 ? this.pricePoints[this.activeDefaultIndex] : this.pricePoints.length ? this.pricePoints[0] : 0;
         this.selectPricePoint(initialValue);
         requestAnimationFrame(() => {
            this.selectPricePoint(this.currentPricePoint, { dispatchEvent: false });
         });
      }

      readPricePointsFromHTML() {
         const markerElements = this.markersContainer.querySelectorAll(".slider-control");

         markerElements.forEach((marker) => {
            const value = this.parseMarkerValue(marker.getAttribute("data-value"));
            const label = marker.querySelector(".slider-label").textContent;
            const tooltipActive = marker.getAttribute("data-tooltip-active") === "yes";
            const tooltipText = marker.getAttribute("data-tooltip-text") ?? "";

            if (marker.getAttribute("data-active") === "yes") {
               this.activeDefaultIndex = this.pricePoints.length;
            }

            this.pricePoints.push(value);
            this.priceLabels.push(label);
            this.priceTooltipActives.push(tooltipActive);
            this.priceTooltipTexts.push(tooltipText);
            this.priceMarkers.push(marker);
         });
      }

      bindEvents() {
         this.handle.addEventListener("mousedown", (e) => {
            this.isDragging = true;
            this.handle.style.cursor = "grabbing";
            this.handle.style.transition = "none";
            if (this.fill) this.fill.style.transition = "none";
            e.preventDefault();
         });

         document.addEventListener("mousemove", (e) => {
            if (!this.isDragging) return;

            const rect = this.sliderBar.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const percent = Math.max(0, Math.min(1, x / rect.width));

            this.updatePositionByPercent(percent);
            this.previewNearestSnap(percent);
         });

         document.addEventListener("mouseup", () => {
            if (!this.isDragging) return;

            this.isDragging = false;
            this.handle.style.cursor = "grab";
            this.handle.style.transition = "";
            if (this.fill) this.fill.style.transition = "";
            this.snapToNearestPriceMarker();
         });

         this.priceMarkers.forEach((marker, index) => {
            marker.addEventListener("click", () => {
               this.selectPricePoint(this.pricePoints[index]);
            });
         });

         this.sliderBar.addEventListener("click", (e) => {
            if (
               e.target === this.handle ||
               e.target.classList.contains("slider-control") ||
               e.target.classList.contains("slider-dot") ||
               e.target.classList.contains("slider-label") ||
               e.target.classList.contains("marker-tooltip")
            ) {
               return;
            }

            const rect = this.sliderBar.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const percent = Math.max(0, Math.min(1, x / rect.width));

            this.snapToNearestPriceMarkerByPercent(percent);
         });

         window.addEventListener("resize", () => {
            this.selectPricePoint(this.currentPricePoint, { dispatchEvent: false });
         });
      }

      selectPricePoint(value, options = {}) {
         const { dispatchEvent = true } = options;
         this.currentPricePoint = value;
         const index = this.pricePoints.indexOf(value);

         if (index !== -1) {
            const percent = this.getMarkerPercent(index);
            this.currentPercent = percent;
            this.handle.style.left = `${percent * 100}%`;
            this.fill.style.width = `${percent * 100}%`;

            this.updateActivePriceMarker(index);
            this.tooltip.textContent = this.getTooltipText(value, index);
            this.updateTooltipVisibility(index);
            this.updateCurve(percent);

            if (dispatchEvent) {
               const event = new CustomEvent(this.eventName, {
                  detail: {
                     value,
                     instanceId: this.instanceId,
                  },
               });
               document.dispatchEvent(event);
            }
         }
      }

      updatePositionByPercent(percent) {
         this.currentPercent = percent;
         this.handle.style.left = `${percent * 100}%`;
         this.fill.style.width = `${percent * 100}%`;

         const currentIndex = this.getMarkerIndexFromPercent(percent);
         const currentValue = this.pricePoints[currentIndex];
         this.tooltip.textContent = this.getTooltipText(currentValue, currentIndex);
         this.updateTooltipVisibility(currentIndex);
         this.updateCurve(percent);
      }

      getPricePointFromPercent(percent) {
         if (!this.pricePoints.length) {
            return null;
         }

         const markerIndex = this.getMarkerIndexFromPercent(percent);
         return this.pricePoints[markerIndex] ?? null;
      }

      getMarkerIndexFromPercent(percent) {
         if (!this.pricePoints.length) {
            return -1;
         }

         let closestIndex = 0;
         let minDistance = Infinity;

         this.pricePoints.forEach((_, index) => {
            const markerPercent = this.getMarkerPercent(index);
            const distance = Math.abs(percent - markerPercent);

            if (distance < minDistance) {
               minDistance = distance;
               closestIndex = index;
            }
         });

         return closestIndex;
      }

      previewNearestSnap(percent) {
         const closestIndex = this.getMarkerIndexFromPercent(percent);

         this.priceMarkers.forEach((marker, index) => {
            marker.classList.toggle("preview-active", index === closestIndex);
         });
      }

      snapToNearestPriceMarker() {
         const closestIndex = this.getMarkerIndexFromPercent(this.currentPercent);

         this.selectPricePoint(this.pricePoints[closestIndex]);

         this.priceMarkers.forEach((marker) => {
            marker.classList.remove("preview-active");
         });
      }

      snapToNearestPriceMarkerByPercent(percent) {
         const closestIndex = this.getMarkerIndexFromPercent(percent);
         this.selectPricePoint(this.pricePoints[closestIndex]);
      }

      getMarkerPercent(index) {
         const marker = this.priceMarkers[index];

         if (!marker || !this.sliderBar) {
            const totalSteps = Math.max(this.pricePoints.length - 1, 1);
            return index / totalSteps;
         }

         const barRect = this.sliderBar.getBoundingClientRect();
         const dot = marker.querySelector(".slider-dot") || marker;
         const dotRect = dot.getBoundingClientRect();

         if (!barRect.width) {
            return 0;
         }

         const centerX = dotRect.left + dotRect.width / 2;
         const rawPercent = (centerX - barRect.left) / barRect.width;

         return Math.max(0, Math.min(1, rawPercent));
      }

      updateActivePriceMarker(activeIndex) {
         this.priceMarkers.forEach((marker, index) => {
            marker.classList.toggle("active", index === activeIndex);
         });
      }

      parseMarkerValue(rawValue) {
         const normalizedValue = normalizeFilterToken(rawValue);

         if (/^-?\d+(\.\d+)?$/.test(normalizedValue)) {
            return Number(normalizedValue);
         }

         return normalizedValue;
      }

      isAllValue(value, index) {
         const normalizedValue = normalizeFilterToken(value);
         const label = this.priceLabels[index] ?? "";
         const normalizedLabel = normalizeFilterToken(label);

         return normalizedValue === "0" || normalizedValue === "all" || normalizedValue === "*" || normalizedLabel === "all";
      }

      getTooltipText(value, index) {
         if (this.priceTooltipActives[index] !== true) {
            return "";
         }

         const customTooltipText = String(this.priceTooltipTexts[index] ?? "").trim();
         if (customTooltipText) {
            return customTooltipText;
         }

         if (this.isAllValue(value, index)) {
            return "All";
         }

         return this.priceLabels[index] ?? String(value);
      }

      updateTooltipVisibility(index) {
         if (!this.tooltip) {
            return;
         }

         if (this.isStyleTwo) {
            this.tooltip.style.opacity = "1";
            return;
         }

         this.tooltip.style.opacity = this.priceTooltipActives[index] === true ? "1" : "0";
      }

      updateCurve(percent) {
         if (!this.isStyleTwo || !this.curvePath || !this.curveSvg) {
            return;
         }

         const barRect = this.sliderBar.getBoundingClientRect();
         const w = barRect.width;
         const h = 55;
         const cy = 5;
         const dipDepth = 40;
         const dipRadius = 100;

         this.curveSvg.setAttribute("viewBox", `0 0 ${w} ${h}`);
         this.curveSvg.style.width = w + "px";
         this.curveSvg.style.height = h + "px";

         const handleX = percent * w;

         const leftEdge = Math.max(0, handleX - dipRadius);
         const rightEdge = Math.min(w, handleX + dipRadius);
         const dipBottom = cy + dipDepth;

         // Smooth U-shaped cosine-wave Bézier approximation
         const d = [
            `M 0 ${cy}`,
            `L ${leftEdge} ${cy}`,
            `C ${leftEdge + (handleX - leftEdge) * 0.4} ${cy}, ${handleX - (handleX - leftEdge) * 0.25} ${dipBottom}, ${handleX} ${dipBottom}`,
            `C ${handleX + (rightEdge - handleX) * 0.25} ${dipBottom}, ${rightEdge - (rightEdge - handleX) * 0.4} ${cy}, ${rightEdge} ${cy}`,
            `L ${w} ${cy}`,
         ].join(" ");

         this.curvePath.setAttribute("d", d);

         this.handle.style.top = `0%`;
      }

      getCurrentPricePoint() {
         return this.currentPricePoint;
      }
   }

   /**
    * This function will show/hide pricing plan accodring to "data-filter".
    */
   function filterPricingPlan(filterValue, plansRoot = document) {
      const plans = plansRoot.querySelectorAll(".eael-pricing-plan");
      let visibleCount = 0;
      const selectedFilter = normalizeFilterToken(filterValue);
      const isAllSelected = selectedFilter === "" || selectedFilter === "0" || selectedFilter === "all" || selectedFilter === "*";

      plans.forEach((plan) => {
         const filterData = plan.getAttribute("data-filter");
         const arrayFilterData = parseFilterTokens(filterData);

         if (isAllSelected || arrayFilterData.includes(selectedFilter)) {
            plan.classList.remove("hidden");
            visibleCount++;
         } else {
            plan.classList.add("hidden");
         }
      });
   }

   function init() {
      const pricingSlider = new PricingSlider($scope);

      // Handle slider changes
      document.addEventListener("pricingSliderChange", (e) => {
         if (!e.detail || e.detail.instanceId !== pricingSlider.instanceId) {
            return;
         }

         const filterValue = e.detail.value;
         filterPricingPlan(filterValue, pricingSlider.plansContainer || $scope[0]);
      });

      // Initial filter
      filterPricingPlan(pricingSlider.getCurrentPricePoint(), pricingSlider.plansContainer || $scope[0]);
   }

   init();
};

jQuery(window).on("elementor/frontend/init", function () {
   if (eael.elementStatusCheck("PricingSliderHandler")) {
      return false;
   }

   elementorFrontend.hooks.addAction("frontend/element_ready/eael-pricing-slider.default", PricingSliderHandler);
});
