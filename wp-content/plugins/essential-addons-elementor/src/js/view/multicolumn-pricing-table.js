
var MulticolumnPricingTable = function($scope, $) {

	if ($.fn.tooltipster) {
		var $tooltips = $scope.find(".eael-pricing-tooltip"),
			i;

		for (i = 0; i < $tooltips.length; i++) {
			var $currentTooltip = $("#" + $($tooltips[i]).attr("id")),
				$tooltipContent =
					$currentTooltip.data("content") !== undefined
						? $currentTooltip.data("content")
						: null,
				$tooltipSide =
					$currentTooltip.data("side") !== undefined
						? $currentTooltip.data("side")
						: false,
				$tooltipTrigger =
					$currentTooltip.data("trigger") !== undefined
						? $currentTooltip.data("trigger")
						: "hover",
				$animation =
					$currentTooltip.data("animation") !== undefined
						? $currentTooltip.data("animation")
						: "fade",
				$anim_duration =
					$currentTooltip.data("animation_duration") !== undefined
						? $currentTooltip.data("animation_duration")
						: 300,
				$theme =
					$currentTooltip.data("theme") !== undefined
						? $currentTooltip.data("theme")
						: "default",
				$arrow = "yes" == $currentTooltip.data("arrow") ? true : false;

			$currentTooltip.tooltipster({
				animation: $animation,
				trigger: $tooltipTrigger,
				content: DOMPurify.sanitize($tooltipContent),
				contentAsHTML: true,
				side: $tooltipSide,
				delay: $anim_duration,
				arrow: $arrow,
				theme: "tooltipster-" + $theme
			});
		}
	}

	let wrapper = $scope.find('.eael-multicolumn-pricing-table-wrapper');
    
    if( wrapper.hasClass( 'collapsable' ) ) {
        let row_cout = wrapper.data('row');
            row_cout = row_cout ? row_cout : 3;

            $(document).on('click', '.eael-mcpt-collaps', function(e){
                $this = $(this);
                $this.toggleClass('collapsed');

                if( ! $this.hasClass('collapsed') ) {
                    $('.eael-mcpt-cell', wrapper).removeClass('hide');
                    $('.eael-mcpt-collaps-label.collaps').removeClass('show');
                    $('.eael-mcpt-collaps-label.open').addClass('show');
                } else {
                    $('.eael-mcpt-collaps-label.open').removeClass('show');
                    $('.eael-mcpt-collaps-label.collaps').addClass('show');
                   $('.eael-mcpt-column', $scope ).each(function(index, column){
                        var cells = $(column).find('.eael-mcpt-cell');
                        cells.each(function(index, cell){
                            if( index > row_cout ){
                                $(this).addClass('hide');
                            }
                        });
                    });
                    
                    $this.removeClass('hide');
                }
            });
    }
};
jQuery(window).on("elementor/frontend/init", function() {

	if (eael.elementStatusCheck("multicolumnPricingTable")) {
		return false;
	}

	elementorFrontend.hooks.addAction(
		"frontend/element_ready/eael-multicolumn-pricing-table.default",
		MulticolumnPricingTable
	);
});
