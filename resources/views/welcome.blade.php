@include('common.header')
<!-- slider -->
<section class="hero" style="background:#0d0d0d url({{ asset('rebuildfrontend/images/banner1.png') }}) center/cover no-repeat">
  <div class="container hero-inner">
    <h1>
      One Home for <br>
      Your Whole <br>
      Hustle <br>
    </h1>
    <p> <span style="color: #Ceff1b;">Create. Sell. Teach. Connect.</span><br>
      All from one powerful platform designed for <br>
      creators and clients who are tired of chaos. <br>
    </p>

    <div class="big-search">
      <i class="fa-solid fa-keyboard" style="color: #ceff1b"></i>
      <input id="welcomeHeroSearch" placeholder='Try "building mobile app"' />
      <button class="go" id="welcomeHeroGo" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass" style="color: #ceff1b"></i>
      </button>
    </div>
    <div class="chips">
      @foreach($types as $t)
        <a href="{{ route('marketplace', ['type_id' => $t->id]) }}"
          class="text-decoration-none text-reset">
          <span class="chip">
            @if($t->icon_class)
            <i class="{{ $t->icon_class }}"></i>
            @endif
            {{ $t->name }}
          </span>
        </a>
      @endforeach
    </div>
  </div>
</section>


<!-- <span class="chip"><i class="fa-solid fa-trowel-bricks"></i> Services</span>
      <span class="chip"><i class="fa-solid fa-bag-shopping"></i> Digital Products</span>
      <span class="chip"><i class="fa-solid fa-graduation-cap"></i> Courses</span>
      <span class="chip"><i class="fa-solid fa-people-group"></i> Webinar
        <i class="fa-solid fa-arrow-right"></i></span>
      <span class="chip"><i class="fa-solid fa-users-between-lines"></i> Teams
        <span class="new">NEW</span></span>
    </div> -->


<!-- =================== SECTION BANNERS =================== -->
<div class="banner" style="background: url('{{ asset('rebuildfrontend/images/banner2.png') }}') center/cover no-repeat">
  <div class="banner-inner container">
    <h2>The Digital Chaos Killing Your Growth</h2>
  </div>
</div>

<!-- =================== SERVICES =================== -->
<section class="servicesbackground">
  <div class="container grid cards-2">
    <p>
      Ever felt like your work is scattered across a dozen tabs, a dozen platforms,
      and a dozen distractions? You're signing into five different accounts just to
      get paid, finding it impossible to keep track of projects, payments, and
      endless opportunities slipping through the cracks. <br><br>
      You're not alone. Independent creators, freelancers, and small agency
      owners everywhere are losing precious time, energy, and potential growth to
      digital fragmentation. While you're busy managing tools, your competitors
      are busy growing their businesses.
    </p>
  </div>
  <div class="services-bottom container grid">
    Every minute spent juggling platforms is a minute not spent
    creating, earning, or scaling your impact.
  </div>
</section>

<div class="banner" style="background: url({{ asset('rebuildfrontend/images/banner2.png') }}) center/cover no-repeat">
  <div class="banner-inner container">
    <h2>Your All-in-One-Solution</h2>
    <p>Imagine a space where everything you need lives under one roof. Ultra Hustle transforms the way independent
      creators work by unifying every aspect of your business into a single, intuitive platform.</p>
  </div>
</div>

<!-- =================== WHY CHOOSE =================== -->
<section>
  <div class="container grid cards-2">
    <article class="card">
      <div class="iconbox">
        <i class="fa-solid fa-trowel-bricks"></i>
      </div>
      <h4 style="font-size: 18px">Launch Services</h4>
      <p>Offer freelance services with professional proposals, contracts, and project management tools.</p>
    </article>
    <article class="card">
      <div class="iconbox"><i class="fa-solid fa-bag-shopping"></i></div>
      <h4 style="font-size: 18px">Sell Products</h4>
      <p>Create and sell digital products with built-in payment processing and instant delivery.</p>
    </article>
    <article class="card">
      <div class="iconbox"><i class="fa-solid fa-graduation-cap"></i></div>
      <h4 style="font-size: 18px">Teach Courses</h4>
      <p>Build and monetize online courses with video hosting and student management features.</p>
    </article>
    <article class="card">
      <div class="iconbox"><i class="fa-solid fa-people-group"></i></div>
      <h4 style="font-size: 18px">Host Events</h4>
      <p>Run webinars, workshops, and virtual events with integrated registration and payment systems.</p>
    </article>
    <article class="card">
      <div class="iconbox"><i class="fa-solid fa-users-between-lines"></i></div>
      <h4 style="font-size: 18px">Build Teams</h4>
      <p>Collaborate on bigger projects by forming flexible teams with creators and experts. Manage roles, assign
        tasks, and track progress seamlessly.</p>
    </article>
  </div>
  <div class="services-bottom container" style="margin-top: 30px;">
    No more switching, syncing, or scrambling between different tools. Ultra Hustle brings your entire creator journey
    together, so you can focus on what matters most: creating exceptional work and scaling your impact.
  </div>
</section>

<div class="banner" style="
        background: url({{ asset('rebuildfrontend/images/banner2.png') }}) center/cover no-repeat;
      ">
  <div class="banner-inner container">
    <h2>Why Ultra Hustle Stands Apart</h2>
  </div>
</div>

<!-- =================== TESTIMONIALS =================== -->
<section>
  <div class="container tgrid">
    <article class="card">
      <h4 style="font-size: 18px; color:#Ceff1b;">All-in-One Platform</h4>
      <p>Ditch the isolated apps cluttering your workflow. Run your entire creator business or agency team from one
        comprehensive account that grows with you.</p>
    </article>
    <article class="card">
      <h4 style="font-size: 18px; color:#Ceff1b;">Seamless Collaboration</h4>
      <p>Clients and creators connect, communicate, and collaborate effortlessly. No more friction, lost
        conversations, or missed opportunities in your inbox.</p>
    </article>
    <article class="card">
      <h4 style="font-size: 18px; color:#Ceff1b;">Trust-Built Payments</h4>
      <p>
        Escrow protection, smart contracts, and milestone-based payments ensure you always get paid on time. No more
        chasing invoices or stressing about cash flow.</p>
    </article>
    <article class="card">
      <h4 style="font-size: 18px; color:#Ceff1b;">Community-Powered Growth</h4>
      <p>
        Connect with teammates, mentors, and supporters who understand your journey. Rise faster when you’re not
        climbing alone.
      </p>
    </article>
  </div>
</section>

<div class="banner" style="background: url({{ asset('rebuildfrontend/images/banner2.png') }}) center/cover no-repeat">
  <div class="banner-inner container">
    <h2>Real Results with Real Creators</h2>
  </div>
</div>

<!-- =================== FAQ =================== -->
<section>
  <div class="container faqgrid">

    @php
      // Ensure we have a collection (avoid undefined errors if controller not wired yet)
      $faqItems = isset($faqs) ? $faqs : collect();
    @endphp

    @forelse($faqItems as $f)
      <article class="card test">
        <div class="txt">
          <p style="color: #Ceff1b; font-size: large;">
            {{ $f->quote }}
          </p>
        </div>
        <div class="foot" style="background-color: #Ceff1b;">
          @php
            $pieces = [];
            if (!empty($f->author_role))     { $pieces[] = $f->author_role; }
            if (!empty($f->author_location)) { $pieces[] = $f->author_location; }
            $meta = implode(', ', $pieces);
          @endphp
          <p style="color: #0e0e0e;">
            {{ $f->author_name }}
            @if($meta)
              <strong> ({{ $meta }})</strong>
            @endif
          </p>
        </div>
      </article>
    @empty
      {{-- Fallback if admin hasn’t added any entries yet --}}
      <article class="card test">
        <div class="txt">
          <p style="color: #Ceff1b; font-size: large;">
            Ultra Hustle saved me weeks of searching. I hired a designer and
            a content writer in under 48 hours, and the milestone-based payments gave me complete peace of mind.
          </p>
        </div>
        <div class="foot" style="background-color: #Ceff1b;">
          <p style="color: #0e0e0e;">Demo User <strong>(Founder, Demo)</strong></p>
        </div>
      </article>
    @endforelse

  </div>

  <div class="services-bottom container" style="margin-top: 30px;">
    Join thousands of creators who have already streamlined their workflows, increased their earnings, and reclaimed
    their time with Ultra Hustle's unified platform approach.
  </div>
</section>

<!-- ================Started in 3 Simple Steps================= -->
<div class="banner" style="background: url({{ asset('rebuildfrontend/images/banner2.png') }}) center/cover no-repeat">
  <div class="banner-inner container">
    <h2>Get Started in Three Simple Get Started in Three Simple </h2>
  </div>
</div>

<section>
  <div class="milan_container ">
    <div class="milan_step">
      <div class="milan_icon"><i class="fa-solid fa-user"></i></div>
      <h3 class="neon">Sign Up in Seconds</h3>
      <p>Create one account that unlocks full creative freedom. No complicated setup, no lengthy onboarding process.
        Just instant access to everything you need.</p>
    </div>

    <div class="milan_step">
      <div class="milan_icon"><i class="fa-solid fa-rocket"></i></div>
      <h3 class="neon">Launch & List</h3>
      <p>Post your first service offering, digital product, or online course. Invite team members, join relevant
        communities, and start building your presence immediately.</p>
    </div>

    <div class="milan_step">
      <div class="milan_icon"><i class="fa-solid fa-handshake"></i></div>
      <h3 class="neon">Connect & Get Paid</h3>
      <p>Book projects, deliver exceptional work, and watch as Ultra Hustle automates payments, feedback collection,
        and team coordination behind the scenes.</p>
    </div>
  </div>
</section>

<!-- ===================Transparent Pricing That Works for You======================================= -->
<div class="banner" style="background: url({{ asset('rebuildfrontend/images/banner2.png') }}) center/cover no-repeat">
  <div class="banner-inner container">
    <h2>Transparent Pricing That Works for You</h2>
  </div>
</div>

<section class="tranparencybanner">
  <div class="container faqgrid">
    <article class=" test">
      <div class="">
        <h3 class="neon">Fair Fees, No Hidden Costs</h3>
        <p>Ultra Hustle believes in complete pricing transparency. You'll always know exactly what you're earning and
          what you're paying for, with no surprise fees or complicated tier structures.</p>
        <ul>
          <li class="neon">Simple percentage-based pricing</li>
          <li class="neon">No monthly subscription fees</li>
          <li class="neon">Free to join and explore</li>
          <li class="neon">Pay only when you earn</li>
          <li class="neon">No hidden transaction costs</li>
        </ul>
        <p>
          Our pricing model aligns with your success—we only succeed when you do. This creates a true partnership
          where Ultra Hustle is invested in helping you grow your business and maximize your earnings.
        </p>
      </div>
      <div class="services-bottom container" style="margin-top: 30px;">
        Start earning immediately with no upfront costs or commitments required.
      </div>
    </article>
  </div>
</section>

<!-- ===================The Cost of Staying Scattered======================================= -->
<div class="banner" style="background: url({{ asset('rebuildfrontend/images/banner2.png') }}) center/cover no-repeat">
  <div class="banner-inner container">
    <h2>The Cost of Staying Scattered</h2>
  </div>
</div>

<section class="milan_stats">
  <div class="milan_row">
    <div class="milan_item">
      <h2 class="neon">15</h2>
      <h3 class="neon">Hours Lost Weekly</h3>
      <p>Average time wasted switching between platforms and managing multiple tools</p>
    </div>
    <div class="milan_item">
      <h2 class="neon">30%</h2>
      <h3 class="neon">Revenue Gap</h3>
      <p>Potential earnings lost to inefficient workflows and missed opportunities</p>
    </div>
    <div class="milan_item">
      <h2 class="neon">67%</h2>
      <h3 class="neon">Stress Increase</h3>
      <p>Higher anxiety levels reported by creators using fragmented tool systems</p>
    </div>
  </div><br>

  <div class="milan_desc">
    <p>
      Every day you continue with scattered tools is another day of missed opportunities, lost time, and reduced
      income potential.
      The digital chaos isn't just inconvenient—it’s actively limiting your growth and success.
    </p>
    <p>
      Ultra Hustle provides the all-in-one foundation you need for building, scaling, and protecting your creative
      business in today's competitive marketplace.
    </p>
  </div>
</section>

<section style="background: url({{ asset('rebuildfrontend/images/Banner_Images.png') }}) center/cover no-repeat">
  <div class="container" style="align-items: center; text-align: center;">
    <img src="{{ asset('rebuildfrontend/images/logo.png') }}" alt="" class="milan_img_logo">
    <h2 class="neon">Ready for Your Next-Level Hustle?</h2><br>
    <h2 class="neon">Make Your First Move in Minutes.</h2><br>
    <p>Transform your scattered workflow into a streamlined success system. Join the thousands of creators who have
      already discovered the power of working from one unified platform.</p><br>
    <a href="#" class="milan_btn_">Start Now</a>
  </div>
</section>
@include('common.footer')
