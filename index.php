<?php
// Hide PHP errors in production
error_reporting(0);
ini_set('display_errors', 0);

include_once 'head.php';
?>

<style>
    .quote-btn {
    display: inline-block;
    padding: 15px 30px;
    border-radius: 5px;
    background: #00c195;
    color: #fff;
    -webkit-transition: .5s all ease;
    transition: .5s all ease;
}
</style>
    <div id="home" class="better-home-area">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-lg-6 col-md-12">
            <div class="better-home-content">
              <!--<span class="sub-title">Partnered with Finwings technologies pvt Ltd</span><br>-->
              <span class="sub-title">Welcome to "CreditLab"<br> - a product of Sonu Marketing Private Limited</span>
              <h1>Get easy loans anywhere anytime</h1>
              <p>
                Fulfill your wishes and needs
                <br />
                with simple, quick and hassle-free loans
              </p>
               <ul class="better-home-btn">
                <li>
                  <a href="account/" class="quote-btn" style="padding: 15px 80px;">Apply Now</a>
                </li>
                <!--<li>-->
                <!--  <a href="#" class="main-optional-btn">About Us</a>-->
                <!--</li>-->
              </ul> 
            </div>
          </div>
          <div class="col-lg-6 col-md-12">
            <div class="better-home-image">
              <img
                src="assets/img/more-home/banner/better-home.png"
                alt="image"
              />
              <a style="display:none" href="" class="video-btn popup-youtube"
                ><i class="bx bx-play"></i
              ></a>
            </div>
          </div>
        </div>
      </div>
    </div>


    <div id="about" class="about-style-area pb-100 mt-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6 col-md-12">
            <div class="about-style-image-wrap">
              <img src="assets/img/moneytree.jpeg" alt="image" />
              <div class="certified-image">
                <img
                  src="assets/img/more-home/about/certified.png"
                  alt="image"
                />
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12" id="how">
            <div class="about-style-wrap-content">
              <!--<span class="sub-title">About Our Company</span>-->
              <h3>We Are Fully Dedicated To Support You</h3>
              <!-- <p class="bold">
                Sed porttitor lectus nibh. Quisque velit nisi, pretium ut
                lacinia in, elementum id enim. Vivamus magna justo lacinia eget
                consectetur.
              </p> -->
              <div class="about-list-tab">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                  <li class="nav-item">
                    <a
                      class="nav-link active"
                      id="about-1-tab"
                      data-bs-toggle="tab"
                      href="#about-1"
                      role="tab"
                      aria-controls="about-1"
                      >How it works?</a
                    >
                  </li>
                  <!-- <li class="nav-item">
                    <a
                      class="nav-link"
                      id="about-2-tab"
                      data-bs-toggle="tab"
                      href="#about-2"
                      role="tab"
                      aria-controls="about-2"
                      >Our Vision
                    </a>
                  </li> -->
                </ul>
                <div class="tab-content" id="myTabContent">
                  <div
                    class="tab-pane fade show active"
                    id="about-1"
                    role="tabpanel"
                  >
                    <div class="content">
                      <!-- <p>
                        Our plan dolor sit amet conseetur diisci velit sed
                        quiLoresum dolor sit ame consectetur adipisicing elit.
                      </p> -->
                      <ul class="list">
                        <li>
                          <i class="bx bx-chevrons-right"></i>
                          Simple Registration
                        </li>
                        <li>
                          <i class="bx bx-chevrons-right"></i>
                          Quick verification
                        </li>
                        <li>
                          <i class="bx bx-chevrons-right"></i> Instant Fund
                          Transfer
                        </li>
                      </ul>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="about-2" role="tabpanel">
                    <div class="content">
                      <p>
                        Our plan dolor sit amet conseetur diisci velit sed
                        quiLoresum dolor sit ame consectetur adipisicing elit.
                      </p>
                      <ul class="list">
                        <li>
                          <i class="bx bx-chevrons-right"></i> Respect for all
                          people
                        </li>
                        <li>
                          <i class="bx bx-chevrons-right"></i> Excellence in
                          everything we do
                        </li>
                        <li>
                          <i class="bx bx-chevrons-right"></i> Truthfulness in
                          our business
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="about-style-shape-1">
        <img src="assets/img/more-home/about/about-shape.png" alt="image" />
      </div>
    </div>

    <div class="mortgage-quote-area-with-full-width">
      <div class="container-fluid">
        <div class="row m-0">
          <div class="col-lg-6 col-md-6 p-0">
            <div class="mortgage-quote-content">
              <h3>Our Loans</h3>
              <ul style="color: #fff">
                <li>100% online</li>
                <li>Minimum Documentation</li>
                <li>Disbursal in 30 minutes</li>
              </ul>
              <a href="account/" class="quote-btn">Apply Now</a>
            </div>
          </div>
          <div class="col-lg-6 col-md-6 p-0">
            <div class="mortgage-quote-image"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="faq-style-area-with-full-width pb-100 mt-5" id="faqs">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-lg-6 col-md-12">
            <div class="faq-style-image">
              <img src="assets/img/more-home/faq.png" alt="image" />
            </div>
          </div>
          <div class="col-lg-6 col-md-12">
            <div class="faq-style-accordion">
              <span class="sub-title">FAQ</span>
              <h3>Need Help? Read Popular Questions</h3>
              <div class="accordion" id="FaqAccordion">
                <div class="accordion-item">
                  <button
                    class="accordion-button"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseOne"
                    aria-expanded="true"
                    aria-controls="collapseOne"
                  >
                    What is the eligibility to apply for loan?
                  </button>
                  <div
                    id="collapseOne"
                    class="accordion-collapse collapse show"
                    data-bs-parent="#FaqAccordion"
                  >
                    <div class="accordion-body">
                      <p>
                        A person with monthly net income of Rs.25000<br />
                        • Applying from Any city in India.
                      </p>
                    </div>
                  </div>
                </div>
                <!--<div class="accordion-item">-->
                <!--  <button-->
                <!--    class="accordion-button collapsed"-->
                <!--    type="button"-->
                <!--    data-bs-toggle="collapse"-->
                <!--    data-bs-target="#collapseTwo"-->
                <!--    aria-expanded="false"-->
                <!--    aria-controls="collapseTwo"-->
                <!--  >-->
                <!--    I am not salaried person, still can i apply for loan?-->
                <!--  </button>-->
                <!--  <div-->
                <!--    id="collapseTwo"-->
                <!--    class="accordion-collapse collapse"-->
                <!--    data-bs-parent="#FaqAccordion"-->
                <!--  >-->
                <!--    <div class="accordion-body">-->
                <!--      <p>-->
                <!--        We currently do not give loans to non-salaried-->
                <!--        applicants. However, you can ask a close acquaintance-->
                <!--        who is salaried to take a loan on their name.-->
                <!--      </p>-->
                <!--    </div>-->
                <!--  </div>-->
                <!--</div>-->
                <div class="accordion-item">
                  <button
                    class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseThree"
                    aria-expanded="false"
                    aria-controls="collapseThree"
                  >
                    what is the maximum loan amount i am eligible for?
                  </button>
                  <div
                    id="collapseThree"
                    class="accordion-collapse collapse"
                    data-bs-parent="#FaqAccordion"
                  >
                    <div class="accordion-body">
                      <p>
                        We give loans upto Rs 6000 - Rs 1 lakh. The sanctioned
                        loan amount depends on your financial and credit history
                        information.
                      </p>
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <button
                    class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseFour"
                    aria-expanded="false"
                    aria-controls="collapseFour"
                  >
                    what are the steps involved to get a loan ?
                  </button>
                  <div
                    id="collapseFour"
                    class="accordion-collapse collapse"
                    data-bs-parent="#FaqAccordion"
                  >
                    <div class="accordion-body">
                      <p>
                        The steps involved are:<br /><br />
                        1. Loan application.<br />
                        2. KYC verification .<br />
                        3. Fill bank account details.<br />
                        4. Loan disbursal.
                      </p>
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <button
                    class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseFive"
                    aria-expanded="false"
                    aria-controls="collapseFive"
                  >
                    How much time does it take to get money in the account?
                  </button>
                  <div
                    id="collapseFive"
                    class="accordion-collapse collapse"
                    data-bs-parent="#FaqAccordion"
                  >
                    <div class="accordion-body">
                      <p>
                        Loan sanction is instant on the website. After that we
                        verify the information from submitted documents. Once
                        approved, money is disbursed in 30min
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

   
    <div class="testimonials-style-area bg-ffffff ptb-100">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-5 col-md-5">
            <div class="testimonials-style-image">
              <img
                src="assets/img/more-home/let-contact-bg.jpg"
                alt="image"
              />
            </div>
          </div>
          <div class="col-lg-7 col-md-7">
            <div class="testimonials-style-content">
              <span class="sub-title">Testimonials</span>
              <h3>People Are Saying About Us.</h3>
              <div class="testimonials-style-slider owl-carousel owl-theme">
                <div class="testimonials-style-card">
                  <div class="info">
                    <i class="bx bxs-quote-alt-left"></i>
                    <h4>Ravi prakash</h4>
                    <span>Tcs</span>
                  </div>
                  <p>
                    “Many websites are there in the market for short term loan I
                    would rate it the best ... process is very smooth and they
                    don't have any hidden charges ..it's few clicks and money
                    gets credited within minutes ... This platform is simple and
                    crystal clear”
                  </p>
                  <ul class="star-list">
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                  </ul>
                </div>
                <div class="testimonials-style-card">
                  <div class="info">
                    <i class="bx bxs-quote-alt-left"></i>
                    <h4>kundan singh</h4>
                    <span>Infosys</span>
                  </div>
                  <p>
                    “creditlab.in is literally very helpful regarding financial
                    matters ... everything in this website is beautifully
                    designed and i am totally satisfied using it ... ”
                  </p>
                  <ul class="star-list">
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                  </ul>
                </div>
                <div class="testimonials-style-card">
                  <div class="info">
                    <i class="bx bxs-quote-alt-left"></i>
                    <h4>kethan</h4>
                    <span>Wipro</span>
                  </div>
                  <p>
                    “Really an awesome ... Can trust and use it when in need of
                    short time cash rotations.. There is no bug or unwanted add.
                    .. And no cheating like membership fee .. realtime
                    processing and approval ..this is what I have experienced
                    ... thankyou team”
                  </p>
                  <ul class="star-list">
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                    <li><i class="bx bx-star"></i></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <section class="miscellaneous-area " style="background:#00c195;color:#fff;padding:50px 0px">
        <br>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <a href="ethical.php">
                    <span style="background:#0d1820;color:#fff;padding:10px;left: 2%;top:15%;">Ethical Lending</span>
                    <img src="https://rush4cash.in/img/left.PNG" style="height:75%;width:100%">
                    </a>
                    </div>
                <div class="col-md-6">
                    <a href="grievance.php">
                    <span style="background:#0d1820;color:#fff;padding:10px;right:2%;top:15%;">Grievance Redressal</span>
                    <img src="https://rush4cash.in/img/right.JPG" style="height:75%;width:100%">
                    </a>
                    </div>
                <center>
                   <p style="color:#fff;"> At Creditlab, we as a team, advise our customer to approach us only if there is an extreme emergency. Therefore, we have strictly instructed our staff to refrain from pushing anyone for a loan. There is a strict policy against any malpractices like:<br>
a) Bribery b)Foul language c)Mental trauma, etc.<br>
</p>

                </center>
            </div>
        </div>
    </section>
<div class="row">
    <div class="col-12" style="background: #0c161d;color: #fff;">
        <div style="padding:25px;">
            <!--<h3 style="color: #fff;">Our lending partners :</h3>-->
            <!--<p>1)	Venus Barter Private Limited (CIN: U51109WB1994PTC062753) is a company incorporated under the Companies Act 2013 and holds registration with the Reserve Bank of India as a Non-Banking Financial Company (NBFC).</p>-->
            <!--        <p>Website : <a href="https://venusbarter.in" target="_blank">venusbarter.in</a></p>-->
            <!--<p>1)	Sonu Marketing Private Limited (CIN: U51909WB1995PTC068572) is a company incorporated under the Companies Act 2013 and holds registration with the Reserve Bank of India as a Non-Banking Financial Company (NBFC).</p>-->
            <!--        <p>Website : <a href="https://www.brahmafinance.in/our-polices/lending-service-providers-digital-lending-apps" target="_blank">brahmafinance.in</a></p>-->
        </div>
    </div>
    </div>
    <div style="display: block;position: fixed;bottom: 55px;right: 0;cursor: pointer;z-index: 15;left: 5px;bottom: 25px;"><a href="/account" class="quote-btn" style="padding: 10px 40px;">Apply now</a></div>
<?php
include_once 'foot.php';
?>