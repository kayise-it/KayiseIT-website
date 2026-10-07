export const kayiseChatbotResponses = [
  {
    intent: "greeting",
    questions: ["hi", "hello", "hey", "good morning", "good afternoon", "good evening"],
    response:
      "Hello. I am the KAYISE IT assistant. I can help with our services, training programs, certification, opportunities, blogs, gallery, partnerships, and contact details."
  },
  {
    intent: "services",
    questions: [
      "what services does kayise it offer",
      "what services do you offer",
      "services",
      "what can kayise it do",
      "website development",
      "it consulting",
      "cyber security training",
      "microsoft office training",
      "computer productivity"
    ],
    response:
      "KAYISE IT offers drone building training, ICT skills training, 4IR technology training, cyber security training, Microsoft Office productivity training, website development, and IT consulting."
  },
  {
    intent: "drone_training",
    questions: [
      "do you offer drone training",
      "drone training",
      "drone workshop",
      "can i learn drones",
      "drone building course south africa",
      "build a drone course"
    ],
    response:
      "Yes. KAYISE IT offers practical drone building training in South Africa. The course includes hands-on assembly, testing, and STEM-based learning for schools and institutions."
  },
  {
    intent: "school_ict_training",
    questions: [
      "do you provide ict training for schools",
      "ict training for schools",
      "school training",
      "technology training for schools",
      "ict training for tvet colleges",
      "ict skills training"
    ],
    response:
      "Yes. We provide ICT skills training for schools and TVET colleges, including practical digital literacy and industry-relevant technology skills."
  },
  {
    intent: "fourir_training",
    questions: [
      "4ir",
      "4ir skills training",
      "fourth industrial revolution",
      "industry 4.0 training"
    ],
    response:
      "KAYISE IT offers 4IR skills training to prepare learners and teams for modern digital environments, innovation, and technology-enabled work."
  },
  {
    intent: "training_skills_page",
    questions: [
      "training and skills",
      "training & skills",
      "training skills",
      "skills training",
      "training",
      "courses",
      "what courses do you offer",
      "training programs",
      "tvet training",
      "school training",
      "microsoft office training",
      "computer productivity",
      "entrepreneurship training",
      "build a drone"
    ],
    response:
      "Our Training & Skills programs include:\n1. ICT Skills Training: computer literacy, internet skills, and digital communication.\n2. Build a Drone Course: hands-on drone assembly, testing, and STEM learning.\n3. Microsoft Office Training: Word, Excel, PowerPoint, and Outlook.\n4. Computer Productivity: file management, collaboration tools, and digital workflow habits.\n5. Cyber Security Training: online safety, password protection, and data security.\n6. Entrepreneurship Training: business thinking, opportunity identification, and startup skills.\nThese programs are designed for schools, TVET colleges, and institutions. You can view full details on the Training & Skills page."
  },
  {
    intent: "website_and_consulting",
    questions: [
      "website development",
      "build website",
      "it consulting",
      "technology consulting"
    ],
    response:
      "We provide professional website development and IT consulting to help organizations improve digital visibility, operations, and technology planning."
  },
  {
    intent: "partnership",
    questions: [
      "how can i partner with kayise it",
      "partner with kayise it",
      "become a partner",
      "partnership opportunities"
    ],
    response:
      "You can partner with KAYISE IT through training delivery, skills development programs, sponsorship, or project collaboration. We actively work with schools, businesses, and government institutions."
  },
  {
    intent: "contact",
    questions: [
      "how do i contact kayise it",
      "contact kayise it",
      "how can i contact you",
      "phone number",
      "email address",
      "email",
      "get in touch"
    ],
    response:
      "You can contact KAYISE IT at info@kayiseit.co.za, call +27 87 702 2625, or use the Contact page form. On the Contact page there is a clickable map you can use to get directions to our office."
  },
  {
    intent: "location",
    questions: [
      "location",
      "located",
      "locate",
      "where is kayise it located",
      "where is kayiseit located",
      "where is the company located",
      "company location",
      "where are you located",
      "where is your office",
      "location of kayise",
      "kayise location",
      "physical address",
      "address",
      "company address",
      "office address",
      "directions",
      "direction",
      "map",
      "gps"
    ],
    response:
      "KAYISE IT is located at Suite 2, 2nd Floor, Nelbro Building, 39B Brown Street, Mbombela. You can also open the Contact page and click the map to get directions."
  },
  {
    intent: "certification",
    questions: [
      "certificate",
      "certification",
      "lms certification",
      "download certificate"
    ],
    response:
      "You can request an LMS certificate on the Certificate page. Use your learner details there and follow the prompts for verification and download."
  },
  {
    intent: "opportunities",
    questions: [
      "opportunities",
      "internship",
      "programs",
      "learnership",
      "jobs"
    ],
    response:
      "Please check the Opportunities page for current programs and openings. You can also use the Contact page to ask about available opportunities and eligibility."
  },
  {
    intent: "blogs_and_gallery",
    questions: [
      "blogs",
      "blog",
      "news",
      "gallery",
      "photos",
      "portfolio"
    ],
    response:
      "You can view insights and updates on our Blogs page and browse project visuals on our Gallery page."
  },
  {
    intent: "about",
    questions: [
      "about",
      "who is kayise it",
      "company profile",
      "about kayise"
    ],
    response:
      "KAYISE IT is a South African technology partner focused on practical ICT services, digital solutions, and skills development for education and business sectors."
  },
  {
    intent: "leadership",
    questions: [
      "who is the founder of kayise it",
      "who is the founder of kayiseit",
      "who is the director of kayise it",
      "who is the director of kayiseit",
      "founder and director",
      "who owns kayise it",
      "who owns kayiseit"
    ],
    response:
      "Leadership details are shared through official KAYISE IT channels. Please contact info@kayiseit.co.za or call +27 87 702 2625 for confirmed founder and director information."
  }
];

export const kayiseFallbackResponse =
  "I can help with public website information such as services, training, opportunities, certification, blogs, gallery, and contact details. If you need more help, please use the Contact page or email info@kayiseit.co.za.";

export const kayiseConfidentialResponse =
  "I cannot share confidential or internal information. I can only assist with public information available on the KAYISE IT website.";

const confidentialKeywords = [
  "password",
  "admin login",
  "credentials",
  "secret",
  "internal",
  "private",
  "confidential",
  "database",
  "sql",
  "server key",
  "api key",
  "token",
  "source code",
  "financial statement",
  "salary",
  "customer list",
  "user data"
];

const stopWords = new Set([
  "a", "an", "and", "are", "as", "at", "be", "by", "can", "do", "for", "from",
  "how", "i", "in", "is", "it", "my", "of", "on", "or", "our", "please", "the",
  "to", "we", "what", "when", "where", "with", "you", "your"
]);

function normalizeText(text = "") {
  return text
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s]/g, " ")
    .replace(/\bopportuniess\b|\bopportunites\b|\boppertunities\b|\boppotunities\b/g, "opportunities")
    .replace(/\bopportunitys\b/g, "opportunities")
    .replace(/\s+/g, " ");
}

function extractKeywords(text = "") {
  return [...new Set(text
    .split(" ")
    .filter((part) => part.length >= 3 && !stopWords.has(part)))];
}

function scoreQuestionMatch(inputKeywordSet, historyKeywordSet, question) {
  const normalizedQuestion = normalizeText(question);
  const keywords = extractKeywords(normalizedQuestion);

  let score = 0;
  for (const keyword of keywords) {
    if (inputKeywordSet.has(keyword)) {
      score += 2;
      continue;
    }

    if (historyKeywordSet.has(keyword)) {
      score += 1;
    }
  }

  return score;
}

function matchesQuestion(normalizedInput, inputKeywordSet, question) {
  const normalizedQuestion = normalizeText(question);
  if (!normalizedQuestion) {
    return false;
  }

  if (!normalizedQuestion.includes(" ")) {
    return inputKeywordSet.has(normalizedQuestion);
  }

  return (` ${normalizedInput} `).includes(` ${normalizedQuestion} `);
}

async function getLiveOpportunitiesResponse() {
  try {
    const res = await fetch('/api/chatbot/opportunities', {
      method: 'GET',
      headers: {
        Accept: 'application/json'
      }
    });

    if (!res.ok) {
      return 'Please check the Opportunities page for current programs and openings. You can also use the Contact page to ask about available opportunities and eligibility.';
    }

    const data = await res.json();
    if (data && typeof data.response === 'string' && data.response.length > 0) {
      return data.response;
    }

    return 'Please check the Opportunities page for current programs and openings. You can also use the Contact page to ask about available opportunities and eligibility.';
  } catch (error) {
    return 'Please check the Opportunities page for current programs and openings. You can also use the Contact page to ask about available opportunities and eligibility.';
  }
}

function contactReply(whatsappDisplay = "") {
  const label = String(whatsappDisplay || "").trim();
  const whatsappBit = label ? `WhatsApp ${label}` : "WhatsApp on the Contact page";

  return `You can contact KAYISE IT at info@kayiseit.co.za, call +27 87 702 2625, ${whatsappBit}, or use the Contact page form. On the Contact page there is a clickable map you can use to get directions to our office.`;
}

export async function getKayiseChatbotResponse(userMessage = "", conversationHistory = [], whatsappDisplay = "") {
  const normalized = normalizeText(userMessage);
  const inputKeywordSet = new Set(extractKeywords(normalized));
  const historyText = normalizeText(conversationHistory.slice(-6).join(" "));
  const historyKeywordSet = new Set(extractKeywords(historyText));

  if (!normalized) {
    return kayiseFallbackResponse;
  }

  const asksConfidential = confidentialKeywords.some((keyword) => normalized.includes(keyword));
  if (asksConfidential) {
    return kayiseConfidentialResponse;
  }

  // Strong keyword override for Training & Skills queries.
  if (inputKeywordSet.has('training') && (inputKeywordSet.has('skills') || inputKeywordSet.has('skill'))) {
    const trainingItem = kayiseChatbotResponses.find((item) => item.intent === 'training_skills_page');
    if (trainingItem) {
      return trainingItem.response;
    }
  }

  for (const item of kayiseChatbotResponses) {
    const matched = item.questions.some((q) => matchesQuestion(normalized, inputKeywordSet, q));
    if (matched) {
      if (item.intent === 'opportunities') {
        return await getLiveOpportunitiesResponse();
      }
      if (item.intent === 'contact') {
        return contactReply(whatsappDisplay);
      }
      return item.response;
    }
  }

  let bestScore = 0;
  let bestResponse = "";

  for (const item of kayiseChatbotResponses) {
    let score = 0;
    for (const question of item.questions) {
      score += scoreQuestionMatch(inputKeywordSet, historyKeywordSet, question);
    }

    if (score > bestScore) {
      bestScore = score;
      bestResponse = item.response;
    }
  }

  if (bestScore >= 2 && bestResponse) {
    const bestItem = kayiseChatbotResponses.find((item) => item.response === bestResponse);
    if (bestItem && bestItem.intent === 'opportunities') {
      return await getLiveOpportunitiesResponse();
    }

    if (bestItem && bestItem.intent === 'contact') {
      return contactReply(whatsappDisplay);
    }

    return bestResponse;
  }

  return kayiseFallbackResponse;
}
