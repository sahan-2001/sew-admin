<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StitchCraft Apparel | Premium Clothing Manufacturing</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #059669;
            --accent: #d97706;
            --light: #f3f4f6;
            --dark: #1f2937;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: #374151;
        }
        
        h1, h2, h3, h4, h5 {
            font-family: 'Montserrat', sans-serif;
        }
        
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1770&q=80');
            background-size: cover;
            background-position: center;
            height: 90vh;
            display: flex;
            align-items: center;
            color: white;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: var(--primary);
        }
        
        .service-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .fabrics-section {
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), url('https://images.unsplash.com/photo-1506629905877-52a5ca6d63b1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1770&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
        }
        
        .process-step {
            position: relative;
            padding-left: 80px;
            margin-bottom: 40px;
        }
        
        .process-step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            position: relative;
        }
        
        .testimonial-card:after {
            content: ''';
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 60px;
            color: var(--light);
            font-family: Arial;
            z-index: 0;
        }
        
        .client-logo {
            filter: grayscale(100%);
            opacity: 0.6;
            transition: all 0.3s ease;
        }
        
        .client-logo:hover {
            filter: grayscale(0);
            opacity: 1;
        }
        
        .cta-section {
            background: linear-gradient(to right, var(--primary), #1e40af);
            color: white;
        }
        
        .footer {
            background: var(--dark);
            color: white;
        }
        
        @media (max-width: 768px) {
            .hero {
                height: 70vh;
                text-align: center;
            }
            
            .process-step {
                padding-left: 0;
                padding-top: 70px;
                text-align: center;
            }
            
            .process-step-number {
                left: 50%;
                transform: translateX(-50%);
                top: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="fixed w-full bg-white shadow-md z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <h1 class="text-2xl font-bold text-indigo-700">StitchCraft<span class="text-amber-600">Apparel</span></h1>
            </div>
            
            <nav class="hidden md:flex space-x-8">
                <a href="#about" class="text-gray-700 hover:text-indigo-600 font-medium">About</a>
                <a href="#services" class="text-gray-700 hover:text-indigo-600 font-medium">Services</a>
                <a href="#process" class="text-gray-700 hover:text-indigo-600 font-medium">Process</a>
                <a href="#materials" class="text-gray-700 hover:text-indigo-600 font-medium">Materials</a>
                <a href="#track-order" class="text-gray-700 hover:text-indigo-600 font-medium">Track Order</a>
                <a href="#contact" class="text-gray-700 hover:text-indigo-600 font-medium">Contact</a>
            </nav>
            
            <div class="md:hidden">
                <button id="menu-toggle" class="text-gray-700">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white py-4 px-4 absolute w-full shadow-lg">
            <a href="#about" class="block py-2 text-gray-700 hover:text-indigo-600">About</a>
            <a href="#services" class="block py-2 text-gray-700 hover:text-indigo-600">Services</a>
            <a href="#process" class="block py-2 text-gray-700 hover:text-indigo-600">Process</a>
            <a href="#materials" class="block py-2 text-gray-700 hover:text-indigo-600">Materials</a>
            <a href="#track-order" class="block py-2 text-gray-700 hover:text-indigo-600">Track Order</a>
            <a href="#contact" class="block py-2 text-gray-700 hover:text-indigo-600">Contact</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero pt-20">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">Premium Apparel Manufacturing Solutions</h1>
                <p class="text-xl mb-8">We transform your clothing ideas into high-quality, market-ready products with precision and care.</p>
                <div class="flex space-x-4">
                    <a href="#contact" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md font-medium">Get a Quote</a>
                    <a href="#services" class="bg-white hover:bg-gray-100 text-gray-800 px-6 py-3 rounded-md font-medium">Our Services</a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">Crafting Quality Apparel Since 2020</h2>
                <p class="text-lg text-gray-600">StitchCraft Apparel is a modern clothing manufacturer focused on delivering exceptional quality and service to fashion brands and startups.</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-medal text-3xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Premium Quality</h3>
                    <p class="text-gray-600">Every garment is crafted with attention to detail and the highest quality standards.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-leaf text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Eco-Friendly</h3>
                    <p class="text-gray-600">We prioritize sustainable practices and materials to reduce our environmental impact.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck-fast text-3xl text-amber-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Fast Turnaround</h3>
                    <p class="text-gray-600">Efficient production processes ensure timely delivery without compromising quality.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-12 section-title">Our Services</h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="service-card bg-white p-6 rounded-lg shadow-md">
                    <div class="w-14 h-14 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-tshirt text-2xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Cut & Sew Manufacturing</h3>
                    <p class="text-gray-600">Full-service apparel production from pattern making to final construction.</p>
                </div>
                
                <div class="service-card bg-white p-6 rounded-lg shadow-md">
                    <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-cut text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Pattern Making</h3>
                    <p class="text-gray-600">Expert pattern creation for your designs with perfect fit and functionality.</p>
                </div>
                
                <div class="service-card bg-white p-6 rounded-lg shadow-md">
                    <div class="w-14 h-14 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-palette text-2xl text-amber-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Design Consultation</h3>
                    <p class="text-gray-600">Professional guidance to optimize your designs for production and market appeal.</p>
                </div>
                
                <div class="service-card bg-white p-6 rounded-lg shadow-md">
                    <div class="w-14 h-14 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-border-all text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Fabric Sourcing</h3>
                    <p class="text-gray-600">Access to a wide network of fabric suppliers for quality materials at competitive prices.</p>
                </div>
                
                <div class="service-card bg-white p-6 rounded-lg shadow-md">
                    <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-certificate text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Quality Control</h3>
                    <p class="text-gray-600">Rigorous inspection at every production stage to ensure flawless garments.</p>
                </div>
                
                <div class="service-card bg-white p-6 rounded-lg shadow-md">
                    <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-boxes-packing text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Packaging & Fulfillment</h3>
                    <p class="text-gray-600">Custom packaging solutions and direct-to-customer shipping services.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section id="process" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-12 section-title">Our Process</h2>
            
            <div class="max-w-4xl mx-auto">
                <div class="process-step">
                    <div class="process-step-number">1</div>
                    <h3 class="text-xl font-semibold mb-2">Consultation & Design</h3>
                    <p class="text-gray-600">We begin by understanding your vision, target audience, and requirements to create a production plan.</p>
                </div>
                
                <div class="process-step">
                    <div class="process-step-number">2</div>
                    <h3 class="text-xl font-semibold mb-2">Pattern Making & Sampling</h3>
                    <p class="text-gray-600">Our experts create precise patterns and develop samples for your approval before full production.</p>
                </div>
                
                <div class="process-step">
                    <div class="process-step-number">3</div>
                    <h3 class="text-xl font-semibold mb-2">Fabric Cutting</h3>
                    <p class="text-gray-600">Using state-of-the-art cutting technology, we ensure precision fabric cutting for consistent results.</p>
                </div>
                
                <div class="process-step">
                    <div class="process-step-number">4</div>
                    <h3 class="text-xl font-semibold mb-2">Sewing & Construction</h3>
                    <p class="text-gray-600">Skilled artisans construct your garments with attention to detail and quality craftsmanship.</p>
                </div>
                
                <div class="process-step">
                    <div class="process-step-number">5</div>
                    <h3 class="text-xl font-semibold mb-2">Quality Check & Finishing</h3>
                    <p class="text-gray-600">Each garment undergoes rigorous quality control before final pressing and finishing.</p>
                </div>
                
                <div class="process-step">
                    <div class="process-step-number">6</div>
                    <h3 class="text-xl font-semibold mb-2">Delivery</h3>
                    <p class="text-gray-600">Your finished products are carefully packaged and delivered to your specified location.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Materials Section -->
    <section id="materials" class="py-16 fabrics-section">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-12 section-title text-white">Premium Materials</h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-2 text-white">Organic Cotton</h3>
                    <p class="text-gray-200">Soft, breathable, and environmentally friendly cotton grown without pesticides.</p>
                </div>
                
                <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-2 text-white">Linen</h3>
                    <p class="text-gray-200">Natural fiber known for its exceptional coolness and freshness in hot weather.</p>
                </div>
                
                <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-2 text-white">Recycled Polyester</h3>
                    <p class="text-gray-200">Sustainable fabric made from recycled plastics with durability and quick-dry properties.</p>
                </div>
                
                <div class="bg-white bg-opacity-10 backdrop-filter backdrop-blur-lg p-6 rounded-lg">
                    <h3 class="text-xl font-semibold mb-2 text-white">Hemp</h3>
                    <p class="text-gray-200">Strong, durable, and eco-friendly material that gets softer with each wash.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Clients Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-12 section-title">Our Clients</h2>
            
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="text-lg text-gray-600">We've had the privilege of working with emerging designers and established brands across the fashion industry.</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center">
                <div class="client-logo flex justify-center">
                    <div class="h-12 w-32 bg-gray-200 rounded-md flex items-center justify-center">
                        <span class="font-semibold text-gray-500">FashionNova</span>
                    </div>
                </div>
                
                <div class="client-logo flex justify-center">
                    <div class="h-12 w-32 bg-gray-200 rounded-md flex items-center justify-center">
                        <span class="font-semibold text-gray-500">UrbanThreads</span>
                    </div>
                </div>
                
                <div class="client-logo flex justify-center">
                    <div class="h-12 w-32 bg-gray-200 rounded-md flex items-center justify-center">
                        <span class="font-semibold text-gray-500">EcoWear</span>
                    </div>
                </div>
                
                <div class="client-logo flex justify-center">
                    <div class="h-12 w-32 bg-gray-200 rounded-md flex items-center justify-center">
                        <span class="font-semibold text-gray-500">StyleHub</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-16">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Bring Your Designs to Life?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">Get in touch with us today to discuss your project and receive a competitive quote.</p>
            <a href="#contact" class="bg-white text-indigo-600 hover:bg-gray-100 px-8 py-3 rounded-md font-medium text-lg">Request a Quote</a>
        </div>
    </section>


    <!-- Track Order Section with BG Image -->
    <section id="track-order" class="relative py-16">
        <div class="absolute inset-0">
            <!-- Background Image -->
            <img src="{{ asset('public/tracking.jpg') }}" 
                alt="Order Tracking Background" 
                class="w-full h-full object-cover">
            <!-- Dark Overlay -->
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        </div>

        <!-- Content on top of BG Image -->
        <div class="relative container mx-auto px-4 grid md:grid-cols-2 gap-8 items-center text-white">
            
            <!-- Left Side: About Order Tracking -->
            <div class="p-8">
                <h2 class="text-3xl font-bold mb-4">Track Your Orders Easily</h2>
                <p class="text-lg leading-relaxed">
                    Use our order tracking system to stay updated on your 
                    <span class="font-semibold">Customer Orders</span>, 
                    <span class="font-semibold">Sample Orders</span>, and 
                    <span class="font-semibold">Purchase Orders</span>. 
                    Just enter your Order ID and Security Code to view live updates instantly.
                </p>
            </div>

            <!-- Right Side: Track Order Form -->
            <div class="bg-white bg-opacity-90 text-gray-900 rounded-lg p-8 shadow-md">
                <form id="track-order-form" class="space-y-6">
                    <div>
                        <label for="order-type" class="block text-gray-700 mb-2 font-medium">Order Type</label>
                        <select id="order-type" name="order_type" class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Select Order Type</option>
                            <option value="customer_order">Customer Order</option>
                            <option value="sample_order">Sample Order</option>
                            <option value="purchase_order">Purchase Order</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="order-id" class="block text-gray-700 mb-2 font-medium">Order ID</label>
                        <input type="text" id="order-id" name="order_id" class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    
                    <div>
                        <label for="random-code" class="block text-gray-700 mb-2 font-medium">Security Code</label>
                        <input type="text" id="random-code" name="random_code" class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <p class="text-sm text-gray-500 mt-1">Enter the security code provided with your order confirmation.</p>
                    </div>
                    
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md font-medium w-full">Track Order</button>
                </form>
                
                <div id="tracking-result" class="mt-6 hidden">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-12 section-title text-center">Contact Us</h2>

            <div class="grid md:grid-cols-2 gap-12">
                <form action="/contact/send" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="name" class="block text-gray-700 mb-2 font-medium">Your Name</label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="email" class="block text-gray-700 mb-2 font-medium">Email Address</label>
                        <input type="email" id="email" name="email" class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="subject" class="block text-gray-700 mb-2 font-medium">Subject</label>
                        <input type="text" id="subject" name="subject" class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>

                    <div>
                        <label for="message" class="block text-gray-700 mb-2 font-medium">Message</label>
                        <textarea id="message" name="message" rows="5" class="w-full px-4 py-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-md font-medium w-full">Send Message</button>
                </form>


                @if(session('success'))
                <!-- Success Modal Overlay -->
                <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full relative">
                        <button id="close-modal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                        <h3 class="text-xl font-semibold text-green-600 mb-4">Success!</h3>
                        <p class="text-gray-700 mb-4">{{ session('success') }}</p>

                        @if(session('emailDetails'))
                        <div class="text-gray-700 text-sm space-y-1">
                            <p><strong>Name:</strong> {{ session('emailDetails.name') }}</p>
                            <p><strong>Sent Email:</strong> {{ session('emailDetails.email') }}</p>
                            <p><strong>Subject:</strong> {{ session('emailDetails.subject') }}</p>
                            <p><strong>Message:</strong> {{ session('emailDetails.message') }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <script>
                    document.getElementById('close-modal').addEventListener('click', function() {
                        document.getElementById('success-modal').style.display = 'none';
                    });
                </script>
                @endif

                <!-- Company Contact Info -->
                <div class="bg-indigo-50 p-8 rounded-xl shadow-md flex flex-col justify-between">
                    <h3 class="text-2xl font-semibold mb-6 text-indigo-700">Get In Touch</h3>

                    <div class="space-y-6">
                        <!-- Address -->
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-indigo-600 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-indigo-800">Address</h4>
                                <p class="text-gray-700 company-address">Loading address...</p>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-phone text-green-600 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-green-800">Phone</h4>
                                <p class="text-gray-700 company-phone">Loading phone...</p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-envelope text-amber-600 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-amber-800">Email</h4>
                                <p class="text-gray-700 company-email">Loading email...</p>
                            </div>
                        </div>

                        <!-- Business Hours -->
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                                <i class="fas fa-clock text-blue-600 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-blue-800">Business Hours</h4>
                                <p class="text-gray-700">Monday - Friday: 9AM - 6PM</p>
                                <p class="text-gray-700">Saturday: 10AM - 4PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <div class="mt-12 w-full h-96 rounded-lg overflow-hidden shadow-md">
        <iframe
            width="100%"
            height="100%"
            frameborder="0"
            style="border:0"
            loading="lazy"
            allowfullscreen
            src="https://www.google.com/maps?q=7.544898077915555,80.42912617709253&hl=en&z=15&output=embed">
        </iframe>
    </div>



    <!-- Footer -->
    <footer class="footer py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 text-white">StitchCraft Apparel</h3>
                    <p class="text-gray-300">Premium clothing manufacturing for brands that value quality, sustainability, and ethical production.</p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4 text-white">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#about" class="text-gray-300 hover:text-white">About Us</a></li>
                        <li><a href="#services" class="text-gray-300 hover:text-white">Services</a></li>
                        <li><a href="#process" class="text-gray-300 hover:text-white">Our Process</a></li>
                        <li><a href="#materials" class="text-gray-300 hover:text-white">Materials</a></li>
                        <li><a href="#contact" class="text-gray-300 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4 text-white">Services</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-white">Apparel Manufacturing</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white">Pattern Making</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white">Design Consultation</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white">Fabric Sourcing</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white">Private Label</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4 text-white">Connect With Us</h4>
                    <div class="flex space-x-4 mb-4">
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center text-white hover:bg-indigo-600">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center text-white hover:bg-blue-400">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center text-white hover:bg-pink-600">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center text-white hover:bg-blue-700">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                    <p class="text-gray-300">Subscribe to our newsletter</p>
                    <div class="mt-2 flex">
                        <input type="email" placeholder="Your email" class="px-4 py-2 rounded-l-md w-full focus:outline-none">
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Subscribe</button>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; 2023 StitchCraft Apparel. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    // Close mobile menu if open
                    document.getElementById('mobile-menu').classList.add('hidden');
                    
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Fetch contact data
        async function loadCompanyContact() {
        try {
            const response = await fetch('/api/company');
            if (!response.ok) throw new Error('Network response was not ok');

            const company = await response.json();

            // Populate the contact info
            document.querySelector('.company-address').textContent = `
                ${company.address_line_1}, 
                ${company.address_line_2 ? company.address_line_2 + ', ' : ''} 
                ${company.address_line_3 ? company.address_line_3 + ', ' : ''} 
                ${company.city}, ${company.postal_code}, ${company.country}
            `;
            document.querySelector('.company-phone').textContent = company.primary_phone;
            document.querySelector('.company-email').textContent = company.email;
        } catch (error) {
            console.error('Error fetching company data:', error);
            document.querySelector('.company-address').textContent = 'Unable to load address';
            document.querySelector('.company-phone').textContent = 'Unable to load phone';
            document.querySelector('.company-email').textContent = 'Unable to load email';
        }
    }

    document.addEventListener('DOMContentLoaded', loadCompanyContact);

    // Success notification of contact form
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('success-modal');
        if(modal) {
            const closeBtn = document.getElementById('close-modal');

            // Close modal when clicking the close button
            closeBtn.addEventListener('click', () => {
                modal.remove();
            });

            // Optional: auto-close after 5 seconds
            setTimeout(() => {
                modal.remove();
            }, 5000);
        }
    });

    // Track order form submission
    document.getElementById('track-order-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const orderType = document.getElementById('order-type').value;
        const orderId = document.getElementById('order-id').value;
        const randomCode = document.getElementById('random-code').value;
        
        // Based on the order type, redirect to the appropriate tracking page
        if (orderType === 'customer_order') {
            window.location.href = `/customer-order/${orderId}/${randomCode}`;
        } else if (orderType === 'sample_order') {
            window.location.href = `/sample-order/${orderId}/${randomCode}`;
        } else if (orderType === 'purchase_order') {
            window.location.href = `/purchase-order/${orderId}/${randomCode}`;
        }
    });
    </script>
</body>
</html>