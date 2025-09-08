(function ($, root, undefined) {

	$(function () {
		'use strict';
		// Document Begin
		$(document).ready(function(){

			/////////////////////////////////////////////////////////////////////////////////
			// Header Functions
			/////////////////////////////////////////////////////////////////////////////////


			// Header on scroll changes class
			$(window).scroll(function() {
			  var scroll = $(window).scrollTop();
			  if (scroll >= 100) {
			     $(".site-header").addClass("scroll-header");
					 $(".mobile-menu .menu").addClass("scroll-menu");
			  } else {
					$(".site-header").removeClass("scroll-header");
			    	$(".mobile-menu .menu").removeClass("scroll-menu");
			  }
			});

			$( ".off-canvas-menu-trigger" ).click(function() {
				$(".wrapper").toggleClass('menu-collapsed');
				$(".site-header").toggleClass('collapsed');
			  });

			  function resize() {
				if ($(window).width() < 700) {
					$(".wrapper").addClass('menu-collapsed');
					$(".site-header").addClass('collapsed');
				} else {
					$(".wrapper").removeClass('menu-collapsed');
					$(".site-header").removeClass('collapsed');
				}
			  }

			  resize();
			
			// Watch for window resize events
			$(window).on('resize', function() {
			  resize();
			});

			  


			$('.accordion-block .content-block .title').click(function() {
				if($(this).parent().hasClass("active")){
					$('.accordion-block .content-block').removeClass('active');

				}
				else{
					$('.accordion-block .content-block').removeClass('active');
					$(this).parent().addClass('active');
				}
				
			});

			$('.site-message .close-container').click(function() {
				$('.site-message').slideToggle();
				document.cookie = "messaging=off; path=/;";
			});

			$('.user-account h4').click(function() {
				$('.account-menu').slideToggle();
			});
			
			
			/////////////////////////////////////////////////////////////////////////////////
			// Share Functions
			/////////////////////////////////////////////////////////////////////////////////
			$('.share-buttons-container .share-toggle').click(function(e) {
				$('.share-buttons').toggleClass('open');
				
			});

			$('.social-share').click(function(e) {
					e.preventDefault();
					window.open($(this).attr('href'), 'fbShareWindow', 'height=450, width=550, top=' + ($(window).height() / 2 - 275) + ', left=' + ($(window).width() / 2 - 225) + ', toolbar=0, location=0, menubar=0, directories=0, scrollbars=0');
					return false;
			});

			/////////////////////////////////////////////////////////////////////////////////
			// Sliders
			/////////////////////////////////////////////////////////////////////////////////
			
				
					
			

		// Document End
		});
	});
})(jQuery, this);
