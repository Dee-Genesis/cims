/* CIMS site search index — internal navigation search, no external service.
   Add a new page? Add one entry below (title, url, section, and a few keywords). */
window.CIMS_PAGES = [
  { title: "Home", url: "index.html", section: "Home", keywords: "homepage welcome" },

  // About Us
  { title: "History", url: "history.html", section: "About Us" },
  { title: "Council Members", url: "council-members.html", section: "About Us" },
  { title: "Overseas Representatives", url: "overseas-representatives.html", section: "About Us" },
  { title: "Legal Standing", url: "legal-standing.html", section: "About Us" },
  { title: "Why Choose Us", url: "why-choose-us.html", section: "About Us" },
  { title: "Professional Recognition", url: "recognition.html", section: "About Us" },
  { title: "Professional Standards & Ethics", url: "professional-standards-ethics.html", section: "About Us" },
  { title: "Policies & Regulations", url: "policies-regulations.html", section: "About Us" },
  { title: "Complaints & Appeals", url: "complaints-appeals.html", section: "About Us" },
  { title: "Management Journal & Publications", url: "management-journal-publications.html", section: "About Us" },

  // Membership
  { title: "Membership Overview", url: "membership.html", section: "Membership" },
  { title: "Associate Member (ACIMS)", url: "membership-associate.html", section: "Membership" },
  { title: "Chartered Member (MCIMS)", url: "membership-chartered.html", section: "Membership" },
  { title: "Chartered Fellow (FCIMS)", url: "membership-fellow.html", section: "Membership" },
  { title: "Chartered Doctoral Fellow (DCIMS)", url: "membership-doctoral-fellow.html", section: "Membership" },
  { title: "Membership Benefits", url: "membership-benefits.html", section: "Membership" },
  { title: "Continuing Professional Development (CPD)", url: "cpd.html", section: "Membership", keywords: "cpd training" },
  { title: "Digital Credentials & Verification", url: "digital-credentials-verification.html", section: "Membership" },

  // Qualifications
  { title: "Specialist Qualifications", url: "specialist-qualifications.html", section: "Qualifications" },
  { title: "Practitioner Qualifications", url: "practitioner-qualifications.html", section: "Qualifications" },
  { title: "Assessment & Quality Assurance", url: "assessment-quality-assurance.html", section: "Qualifications" },
  { title: "Chartered Strategic Director (CSD)", url: "chartered-strategic-director-csd.html", section: "Qualifications" },

  // Employers
  { title: "Corporate Membership", url: "employers-corporate-membership.html", section: "Employers" },
  { title: "Hire a CIMS Member", url: "employers-hire-member.html", section: "Employers" },
  { title: "Accredited Training Partners", url: "accredited-training-partners.html", section: "Employers" },
  { title: "Become an Accredited Training Partner", url: "become-accredited-training-partner.html", section: "Employers", keywords: "apply application" },

  // Member Area
  { title: "Apply Now", url: "app.html", section: "CPD Portal", keywords: "apply application join start" },
  { title: "Register", url: "register.html", section: "CPD Portal", keywords: "sign up account" },
  { title: "Member Log In", url: "member-login.html", section: "CPD Portal", keywords: "sign in login" },
  { title: "Admin Log In", url: "admin-login.html", section: "CPD Portal" },

  // Footer / other
  { title: "FAQ", url: "faq.html", section: "Help", keywords: "questions help support" },
  { title: "Certificate Verification", url: "certificate-verification.html", section: "Help", keywords: "verify credential" },
  { title: "Privacy Policy", url: "privacy.html", section: "Help" },
  { title: "Contact Us", url: "contact-us.html", section: "Help", keywords: "email phone address" },
  { title: "Annual Conference Gallery", url: "gallery-annual-conference.html", section: "Events", keywords: "photos gallery" }
];

/* Simple, dependency-free matcher: scores on title first, then section/keywords. */
window.CIMS_SEARCH = function (query) {
  const q = (query || "").trim().toLowerCase();
  if (!q) return [];
  const terms = q.split(/\s+/).filter(Boolean);

  return window.CIMS_PAGES
    .map(page => {
      const haystack = [page.title, page.section, page.keywords || ""].join(" ").toLowerCase();
      let score = 0;
      terms.forEach(term => {
        if (page.title.toLowerCase().startsWith(term)) score += 5;
        else if (page.title.toLowerCase().includes(term)) score += 3;
        else if (haystack.includes(term)) score += 1;
      });
      return { page, score };
    })
    .filter(r => r.score > 0)
    .sort((a, b) => b.score - a.score)
    .map(r => r.page);
};
