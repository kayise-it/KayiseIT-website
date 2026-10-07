import $ from 'jquery';
window.$ = window.jQuery = $;

import 'bootstrap';

// Import GSAP
import { gsap } from 'gsap';

// Make GSAP available globally
window.gsap = gsap;

import Alpine from 'alpinejs';
import { getKayiseChatbotResponse } from './chatbotResponses';
import AOS from 'aos';
import 'aos/dist/aos.css';

window.Alpine = Alpine;

Alpine.start();

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
AOS.init({
    once: true,
    duration: 600,
    offset: 40,
    disable: prefersReducedMotion,
});

function callback(event) {
    removeSliderClass(event);
}

function removeSliderClass(event) {
    console.log("removing classes");
    var item = event.item.index - 2; // Position of the current item
    console.log(item);
    jQuery('p').removeClass('animate__animated animate__fadeInDown');
    jQuery('h1').removeClass('animate__animated animate__fadeInUp');
    jQuery('img').removeClass('animate__animated animate__fadeInDown');
    jQuery('.hero__btn').removeClass('animate__animated animate__fadeInLeft');

    jQuery('.owl-item').not('.cloned').eq(item).find('p').addClass('animate__animated animate__fadeInDown');
    jQuery('.owl-item').not('.cloned').eq(item).find('h1').addClass('animate__animated animate__fadeInUp');
    jQuery('.owl-item').not('.cloned').eq(item).find('img').addClass('animate__animated animate__fadeInUp');
    jQuery('.owl-item').not('.cloned').eq(item).find('.hero__btn').addClass('animate__animated animate__fadeInLeft');
}
function deleteSelected() {
    const selectedIds = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    const selectedIdsArray = Array.from(selectedIds).map(input => input.value);
    document.getElementById('selected-ids-input').value = JSON.stringify(selectedIdsArray);
    document.getElementById('delete-selected-form').submit();
}

function deleteRow(service_id) {
    if (confirm('Are you sure you want to delete this service?')) {
        // Create a form element and submit it to delete the individual row
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "/dashboard/services/deleteservice/" + service_id; // Use string concatenation
        form.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Initial state: Show the first slide and add 'show' class to the first subserv_card
$(".slide:first").addClass("show");
$(".subserv_card:first").addClass("show");

// Handle click event on subserv_card elements
$(".subserv_card").click(function () {
    var target = $(this).data("target"); // Get the data-target value
    // Hide all slides and remove 'show' class from all subserv_card elements
    $(".slide").removeClass("show");
    $(".subserv_card").removeClass("show");

    // Remove the previously added classes 'border-5' and 'border-green-500' from all subserv_card elements
    $(".subserv_card").removeClass("border-5 border-green-500");

    // Show the selected slide and add 'show' class to the clicked subserv_card
    $("#" + target).addClass("show");
    $(this).addClass("show");

    // Add the 'border-5' and 'border-green-500' classes to the clicked subserv_card
    $(this).addClass("border-5 border-green-500");
});
$('#checkbox-all').click(function () {
    // Check or uncheck all checkboxes based on the state of the checkbox-all.
    $('.checkbox').prop('checked', this.checked);
});

// Event listener for pagination links
// For Employees
$(document).on('click', '.pagination-employees a', function (event) {
    event.preventDefault();
    let page = $(this).attr('href').split('employeePage=')[1];
    fetchList('employees', page);
});

// For Carousel
$(document).on('click', '.pagination-carousel a', function (event) {
    event.preventDefault();
    let page = $(this).attr('href').split('carouselPage=')[1];
    fetchList('carousel', page);
});

function fetchList(listType, page) {
    $.ajax({
        url: `/dashboard/${listType}?page=${page}`,
        type: "get",
        dataType: "json",
        success: function (data) {
            $(`.${listType}-container`).html(data.html); // Update the list container
            $(`.pagination-${listType}`).html(data.pagination); // Update the pagination links
        },
        error: function (xhr, status, error) {
            console.error("An error occurred: " + status + "\nError: " + error);
        }
    });
}



function slideBoxes(direction) {
    var boxes = document.querySelectorAll(".custom-container .box");
    var currentIndex = 0; // Index of the current visible box

    // Find the current visible box
    boxes.forEach(function (box, index) {
        var computedStyle = window.getComputedStyle(box);
        if (computedStyle.transform === 'none' || computedStyle.transform.includes('matrix(1, 0, 0, 1, 0, 0)')) {
            currentIndex = index;
        }
    });

    // Calculate the next index based on direction
    var nextIndex = direction === 'right' ? currentIndex + 1 : currentIndex - 1;

    // Boundary checks
    if (nextIndex >= boxes.length) nextIndex = 0;
    if (nextIndex < 0) nextIndex = boxes.length - 1;

    // Slide out the current box and slide in the next box
    boxes[currentIndex].style.transform = direction === 'right' ? "translateX(-100%)" : "translateX(100%)";
    boxes[nextIndex].style.transform = "translateX(0)";
}

// Make slideBoxes globally available
window.slideBoxes = slideBoxes;

document.addEventListener('DOMContentLoaded', function () {
    const chatbotRoot = document.getElementById('kayise-chatbot');
    if (!chatbotRoot) {
        return;
    }

    const toggleBtn = document.getElementById('kayise-chatbot-toggle');
    const closeBtn = document.getElementById('kayise-chatbot-close');
    const panel = document.getElementById('kayise-chatbot-panel');
    const form = document.getElementById('kayise-chatbot-form');
    const input = document.getElementById('kayise-chatbot-input');
    const messages = document.getElementById('kayise-chatbot-messages');
    const quickReplyButtons = document.querySelectorAll('.kayise-chatbot-quick-reply');

    if (!toggleBtn || !closeBtn || !panel || !form || !input || !messages) {
        return;
    }

    const chatInputMaxHeight = 180;
    const conversationHistory = [];

    const resizeChatInput = function () {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, chatInputMaxHeight)}px`;
        input.style.overflowY = input.scrollHeight > chatInputMaxHeight ? 'auto' : 'hidden';
    };

    const addMessage = function (text, sender) {
        const msg = document.createElement('div');
        msg.className = `kayise-chatbot-message ${sender}`;
        msg.textContent = text;
        messages.appendChild(msg);
        messages.scrollTop = messages.scrollHeight;
        return msg;
    };

    const addTypingMessage = function () {
        const typingMsg = document.createElement('div');
        typingMsg.className = 'kayise-chatbot-message bot';
        typingMsg.textContent = 'KAYISE IT Assistant is typing...';
        messages.appendChild(typingMsg);
        messages.scrollTop = messages.scrollHeight;
        return typingMsg;
    };

    // Open/close state is controlled in layouts/app.blade.php to avoid conflicts across pages.

    const handleQuestion = function (questionText) {
        if (!questionText) {
            return;
        }

        const historySnapshot = conversationHistory.slice(-6);
        addMessage(questionText, 'user');
        const typingMsg = addTypingMessage();

        setTimeout(async function () {
            try {
                const whatsappDisplay = chatbotRoot.dataset.whatsappDisplay || '';
                const reply = await getKayiseChatbotResponse(questionText, historySnapshot, whatsappDisplay);
                typingMsg.textContent = reply;
                messages.scrollTop = messages.scrollHeight;
            } catch (error) {
                typingMsg.textContent = 'Please check the Opportunities page for current programs and openings. You can also use the Contact page to ask about available opportunities and eligibility.';
                messages.scrollTop = messages.scrollHeight;
            }

            conversationHistory.push(questionText);
            if (conversationHistory.length > 10) {
                conversationHistory.shift();
            }
        }, 3000);
    };

    quickReplyButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const question = button.getAttribute('data-question');
            handleQuestion(question);
        });
    });

    input.addEventListener('input', resizeChatInput);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    resizeChatInput();

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const message = input.value.trim();
        if (!message) {
            return;
        }

        handleQuestion(message);
        input.value = '';
        resizeChatInput();
    });
});
