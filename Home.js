const facts = [
{
    text: "Low-fat products often contain more added sugar.",
    link: "https://theslimmingclinic.com/blog/hidden-sugar-in-low-fat-foods"
},
{
    text: "Over 70% of packaged foods contain added sugars.",
    link: "https://www.georgeinstitute.org/news-and-media/news/the-piles-of-sugar-hidden-from-shoppers-by-food-manufacturers"
},
{
    text: "Fruit juices can have as much sugar as soft drinks.",
    link: "https://www.healthline.com/nutrition/fruit-juice-vs-soda#sugar-content"
},
{
    text: "Zero trans fat doesn't always mean zero.",
    link: "https://www.nbcnews.com/health/health-news/zero-trans-fat-doesnt-always-mean-none-flna1c9468893"
},
{
    text: "Artificial food dyes may affect behavior and attention in children.",
    link: "https://pmc.ncbi.nlm.nih.gov/articles/PMC9052604/"
},
{
    text: "Energy drinks can equal multiple cups of coffee.",
    link: "https://www.uhhospitals.org/blog/articles/2023/08/are-energy-drinks-more-harmful-than-coffee"
},
{
    text: "Natural flavors can be made from dozens of processed components.",
    link: "https://www.healthline.com/nutrition/natural-flavors#consuming-natural-flavors"
},
{
    text: "Multigrain doesn’t always mean whole grain.",
    link: "https://studyfinds.org/multigrain-whole-grain/"
}
];

let factIndex = 0;
let charIndex = 0;
const speed = 40;

function typeEffect() {
    const factElement = document.getElementById("factText");
    const currentFact = facts[factIndex];

    if (!factElement) return;

    if (charIndex < currentFact.text.length) {
        factElement.innerHTML += currentFact.text.charAt(charIndex);
        charIndex++;
        setTimeout(typeEffect, speed);
    } else {
        factElement.innerHTML = `<a href="${currentFact.link}" target="_blank">${currentFact.text}</a>`;

        const delay = Math.random() * 5000 + 5000;

        setTimeout(() => {
            factElement.innerHTML = "";
            charIndex = 0;
            factIndex = (factIndex + 1) % facts.length;
            typeEffect();
        }, delay);
    }
}
let typingTimeout;

function typeWriter(element, text, speed = 30) {
    clearTimeout(typingTimeout);
    element.innerHTML = "";
    let i = 0;

    function typing() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            typingTimeout = setTimeout(typing, speed);
        }
    }

    typing();
}

function goHome() {
    window.location.href = "index.html";
}

document.addEventListener("DOMContentLoaded", function() {

    // START FACTS
    typeEffect();

    const homeButton = document.getElementById("HomeButton");
    if (homeButton) {
        homeButton.addEventListener("click", goHome);
    }

    // MOOD SECTION
    const moodSlider = document.getElementById("moodSlider");
    const moodValue = document.getElementById("moodValue");
    const moodResult = document.getElementById("moodResult");

    if (!moodSlider || !moodValue || !moodResult) {
    console.log("Mood elements not found");
} else {
    const moodSuggestions = {
        low: "You seem low on energy. Consider foods rich in iron, magnesium, and natural sugars like fruits, nuts, and dark chocolate.",
        mid: "You're doing okay. Maintain balance with proteins, whole grains, and hydration.",
        high: "You're feeling great! Keep it up with nutrient-rich foods and avoid excess sugar spikes."
    };

    function updateMood(value) {
        moodValue.textContent = value;

        if (value <= 3) {
            typeWriter(moodResult, moodSuggestions.low);
        } else if (value <= 7) {
            typeWriter(moodResult, moodSuggestions.mid);
        } else {
            typeWriter(moodResult, moodSuggestions.high);
        }
    }

    moodSlider.addEventListener("input", function () {
        updateMood(this.value);
    });

    updateMood(moodSlider.value);
}
});


