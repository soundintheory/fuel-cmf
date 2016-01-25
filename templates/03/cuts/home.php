<?php include 'includes/header.php' ?>

	<section id="top-banner" class="container">
			<div class="homepage-slider row">
				<div class="flexslider">
					<ul class="slides">
					    <li>
					      	<div class="slide-content">
								<h1 class="title">I'm a title or a message in a striking font</h1>
								<p class="text">Space for some more text to go here, or maybe here. Definitely not here though!</p>
								<a class="cta sc inverse" href="#">Click Me</a>
							</div>
							<img src="http://placehold.it/640x360&text=Slide+1" />
					    </li>
					    <li>
					    	<div class="body-list">
						      	<div class="slide-content">
									<h1 class="title">Another Slide Title</h1>
									<p class="text">Some text to go underneath the title here</p>
									<a class="cta sc inverse" href="#">Custom CTA text</a>
								</div>
								<img src="http://placehold.it/640x360&text=Slide+2" />
							</div>
					    </li>
					    <li>
					    	<div class="body-list"> 	
						      	<div class="slide-content">
									<h1 class="title">I'm a title or a message in a striking font</h1>
									<p class="text">Space for some more text to go here, or maybe here. Definitely not here though!</p>
									<a class="cta sc inverse" href="#">Click Me</a>
								</div>
								<img src="http://placehold.it/640x360&text=640x360" />
							</div>
					    </li>
					</ul>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			$(document).ready(function() {

				// Reference: http://www.woothemes.com/flexslider/
				$('.homepage-slider').flexslider({
					animation: "slide",
					selector: ".slides > li",
					slideshow: true,
					slideshowSpeed: 7000,
					animationSpeed: 800,
					smoothHeight: false,
					controlNav: true, // Generates pagination
					directionNav: true // Generates next / prev arrows
				});

			});
		</script>
	</section> <!-- top-banner -->

	<section class="body container pad-top pad-bot">
		<div class="block-heading">
			<div>
				<h3 class="heading">Three column layout</h3>
			</div>
		</div>
		<div class="item-list row body-top">
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>
						<h3>Lorem Ipsum Proin gravida nibh velit auctora</h3>
						<p>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</p>
					</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>
						<h3>Lorem Ipsum Proin gravida nibh velit auctora</h3>
						<p>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</p>
					</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>
						<h3>Lorem Ipsum Proin gravida nibh velit auctora</h3>
						<p>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</p>
					</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>
						<h3>Lorem Ipsum Proin gravida nibh velit auctora</h3>
						<p>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</p>
					</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>
						<h3>Lorem Ipsum Proin gravida nibh velit auctora</h3>
						<p>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</p>
					</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>
						<h3>Lorem Ipsum Proin gravida nibh velit auctora</h3>
						<p>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</p>
					</figcaption>
				</figure>
			</a>
		</div>
		<div class="block-heading">
			<div>
				<h3 class="heading">Four column layout</h3>
			</div>
		</div>
		<div class="item-list body-middle row">
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x250&text=400x250" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x250&text=400x250" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x250&text=400x250" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x250&text=400x250" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
		</div>
	</section> <!-- outer -->

<?php include 'includes/footer.php' ?>