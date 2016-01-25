<?php include 'includes/header.php' ?>

	<section id="top-banner" class="container">
		<div class="row">
			<div class="homepage-slider">
				<div class="flexslider">
				  <ul class="slides">
				    <li>
				      <img src="http://placehold.it/1175x500&text=Slide+1" class="slide-img"/>
				      	<div class="slide-content">
							<h1 class="title">I'm a title or a message in a striking font</h1>
							<p class="text">Space for some more text to go here, or maybe here. Definitely not here though!</p>
							<a class="cta sc inverse" href="#">Click Me</a>
						</div>
				    </li>
				    <li>
				      <img src="http://placehold.it/1175x500&text=Slide+2" class="slide-img"/>
				      	<div class="slide-content">
							<h1 class="title">Another Slide Title</h1>
							<p class="text">Some text to go underneath the title here</p>
							<a class="cta sc inverse" href="#">Custom Cta Text</a>
						</div>
				    </li>
				    <li>
				      <img src="http://placehold.it/1175x500&text=1175x500" class="slide-img"/>
				      	<div class="slide-content">
							<h1 class="title">I'm a title or a message in a striking font</h1>
							<p class="text">Space for some more text to go here, or maybe here. Definitely not here though!</p>
							<a class="cta sc inverse" href="#">Click Me</a>
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
	</section> <!-- outer -->

	<section class="body container">
		<div class="item-list overlay row body-top">
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
			<a href="#" class="item">
				<figure>
					<img src="http://placehold.it/400x300&text=400x300" />
					<figcaption>This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet aenean sollicitudin</figcaption>
				</figure>
			</a>
		</div>
		<nav class="breadcrumbs">
			<ul>
				<li class="on"><a href="#">Alternative navigation</a></li>
				<li><a href="#">Another link</a></li>
				<li><a href="#">Another link</a></li>
				<li><a href="#">Another link</a></li>
				<li><a href="#">Another link</a></li>
				<li><a href="#">Another link</a></li>
			</ul>
		</nav>

		<div class="block-heading">
			<div>
				<h3 class="heading">This is the heading of the next section</h3>
				<a href="#">All items</a>
			</div>
		</div>
		<div class="item-list row body-middle">
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
		<div class="item-list body-bottom">
			<div class="row">
				<div class="item" data-height-group="1">
					<h3 class="heading">Another Section</h3>
					<p class="text">This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet. Aenean</p>
				</div>
				<div class="item" data-height-group="1">
					<h3 class="heading">Another Section</h3>
					<p class="text">This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet. Aenean</p>
				</div>
				<div class="item" data-height-group="1">
					<h3 class="heading">Another Section</h3>
					<p class="text">This is Photoshop's version  of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet. Aenean</p>
				</div>
			</div>
		</div>
	</section> <!-- body -->

<?php include 'includes/footer.php' ?>