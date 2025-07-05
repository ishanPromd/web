const questionElement = document.getElementById('question-container');
        const prevButton = document.getElementById('prev-btn');
        const nextButton = document.getElementById('next-btn');
        const resultElement = document.getElementById('result');
        const summaryElement = document.getElementById('summary');
        const timerElement = document.getElementById('timer');
        const progressBar = document.getElementById('progress-bar');
        const totalQuestionsElement = document.getElementById('total-questions');
        const correctAnswersElement = document.getElementById('correct-answers');
        const incorrectAnswersElement = document.getElementById('incorrect-answers');
        const percentageElement = document.getElementById('percentage');
        const submittedMessage = document.getElementById('submitted-message');

        let currentQuestionIndex = 0;
        let score = 0;
        let timeLeft = 610; // 5 minutes in seconds
        let timerInterval;
        let userAnswers = []; // Array to store user's answers

        const questions = [
            {
                question: "තාප ධාරිතාවේ සම්මත ඒකකය වනුයේ,",
                answers: [
                    { text: "J", correct: true },
                    { text: "JK -¹", correct: false },
                    { text: "K -¹", correct: false },
                    { text: "J Kg -¹ K -¹", correct: false },
                    { text: "Js -¹", correct: false }
                ]
            },
            {
                question: "විශිෂ්ට තාප ධාරිතාව 1200 J Kg -¹ K -¹ වූ ද්‍රව්‍ය 50g ප්‍රමාණයකින් සාදන ලද උපකරණයකට 1000J තාපයක් ලබාදීමෙන් සිදුකරගත හැකි උෂ්ණත්ව වෙනස සොයන්න,",
                answers: [
                    { text: "12 KJ", correct: false },
                    { text: "60 000 J", correct: true },
                    { text: "6 KJ", correct: false },
                    { text: "120 000 J", correct: false },
                    { text: "12 500 J", correct: false }
                ]
            },

            {
               question: "ජලය 1 Kg සහිත තඹ බඳුනක් 15 °C සිට 35 °C දක්වා රත් කිරීමට අවශ්‍ය තාප ප්‍රමාණය කොහොමද,",
               answers: [
                   { text: "84 000 J", correct: false },
                   { text: "98 000 J", correct: true },
                   { text: "14 000J", correct: false },
                   { text: "18 000 J", correct: false },
                   { text: "10 800J", correct: false }
               ]
           },

           {
            question: "පහත ප්‍රකාශ වලින් අසත්‍යය ප්‍රකාශය වනුයේ,",
            answers: [
                { text: "සෑම විටම උෂ්ණත්වය වැඩි වස්තුවේ සිට අඩු වස්තුව දක්වා තාපයේ ගමන්කරයි.", correct: false },
                { text: "වස්තුවක ශක්ති මට්ටම එහි උෂ්ණත්වයයි.", correct: true },
                { text: "සෑම විටම විශිෂ්ට තාප ධාරිතාව වැඩි වස්තුවේ සිට අඩු වස්තුව දක්වා තාපය ගලයි.", correct: false },
                { text: "විශිෂ්ට තාප ධාරීතාවය ස්කන්ධය මත රඳා පවතී.", correct: false },
                { text: "තාප ධාරිතාව ස්කන්ධය මතවෙනස් වේ.", correct: false }
            ]
        },
            {
                question:[
                  "පරිසරයට තාප හානියක් නොවන සේ ලෝහ බඳුනක් තුළ උණුසුම් ද්‍රව්‍යයක් හා සිසිල් ද්‍රව්‍යයක් මිශ්‍ර කරන විට, &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  A - උණුසුම් ද්‍රව්‍ය සිසිල් වන අතර, සිසිල් ද්‍රව්‍යය උණුසුම් වේ.                     " ,
                  " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  B - පද්ධතිය අවසාන පොදු නියත උෂ්ණත්වයකට පැමිණේ." ,
                  " &nbsp; &nbsp;  &nbsp; &nbsp; C - උණුසුම් ද්‍රව්‍ය පිට කළ තාපයේ සිසි ද්‍රව්‍ය හා බඳුන ලබාගත් තාපයට සමාන වේ."],
                answers: [
                    { text: "A පමණි", correct: true },
                    { text: "B පමණි", correct: false },
                    { text: "A හා B පමණී", correct: false },
                    { text: "B හා C පමණී", correct: false },
                    { text: "A , B , C පමණී", correct: false }
                ]
            },
        ];

        function startTimer() {
            timerInterval = setInterval(() => {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = `Time Left: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                if (timeLeft <= 30) {
                    timerElement.classList.add('low-time');
                }
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    endExam();
                }
            }, 1000);
        }

        function showQuestion(index) {
            const question = questions[index];
            questionElement.innerHTML = `
                <div class="question-number">Question ${index + 1}</div>
                <div class="question">${question.question}</div>
                <div class="options">
                    ${question.answers.map((answer, i) => `
                        <div class="option">
                            <input type="radio" name="answer" id="answer${i + 1}" 
                                   value="${answer.text}" 
                                   ${userAnswers[index] === answer.text ? 'checked' : ''}>
                            <label for="answer${i + 1}">${answer.text}</label>
                        </div>
                    `).join('')}
                </div>
            `;

            // Add event listeners to radio buttons to handle selection and deselection
            document.querySelectorAll('input[name="answer"]').forEach(radio => {
                radio.addEventListener('click', (e) => {
                    if (userAnswers[currentQuestionIndex] === e.target.value) {
                        // If the same answer is clicked again, deselect it
                        e.target.checked = false;
                        userAnswers[currentQuestionIndex] = undefined;
                    } else {
                        // Otherwise, update the selected answer
                        userAnswers[currentQuestionIndex] = e.target.value;
                    }
                    updateProgressBar();
                });
            });

            prevButton.classList.toggle('hide', index === 0);
            nextButton.textContent = index === questions.length - 1 ? 'Submit' : 'Next';
        }

        function updateProgressBar() {
            const answeredQuestions = userAnswers.filter(answer => answer !== undefined).length;
            const progress = (answeredQuestions / questions.length) * 100;
            progressBar.style.width = `${progress}%`;
        }

        function endExam() {
            clearInterval(timerInterval);
            questionElement.classList.add('hide');
            prevButton.classList.add('hide');
            nextButton.classList.add('hide');
            resultElement.classList.add('hide');

            // Show submitted message
            submittedMessage.classList.remove('hide');

            // Throw stars
            throwStars();

            // Show results after a delay
            setTimeout(() => {
                submittedMessage.classList.add('hide');
                summaryElement.classList.remove('hide');
                totalQuestionsElement.textContent = questions.length;
                correctAnswersElement.textContent = score;
                incorrectAnswersElement.textContent = questions.length - score;
                percentageElement.textContent = ((score / questions.length) * 100).toFixed(2);
            }, 2000); // 2 seconds delay
        }

        function throwStars() {
            const submitButton = document.getElementById('next-btn');
            const buttonRect = submitButton.getBoundingClientRect();
            const buttonCenterX = buttonRect.left + buttonRect.width / 2;
            const buttonCenterY = buttonRect.top + buttonRect.height / 2;

            for (let i = 0; i < 20; i++) {
                const star = document.createElement('div');
                star.classList.add('star');
                star.style.top = `${buttonCenterY}px`;
                star.style.left = `${buttonCenterX}px`;
                star.style.width = `${Math.random() * 5 + 2}px`;
                star.style.height = star.style.width;
                star.style.setProperty('--x', `${Math.random() * 200 - 100}px`);
                star.style.setProperty('--y', `${Math.random() * 200 - 100}px`);
                document.body.appendChild(star);

                // Remove star after animation
                star.addEventListener('animationend', () => {
                    star.remove();
                });
            }
        }

        prevButton.addEventListener('click', () => {
            saveAnswer();
            currentQuestionIndex--;
            showQuestion(currentQuestionIndex);
        });

        nextButton.addEventListener('click', () => {
            saveAnswer();
            const selectedAnswer = document.querySelector('input[name="answer"]:checked');
            if (selectedAnswer) {
                const correct = questions[currentQuestionIndex].answers.find(answer => answer.text === selectedAnswer.value).correct;
                if (correct) score++;
            }
            if (currentQuestionIndex < questions.length - 1) {
                currentQuestionIndex++;
                showQuestion(currentQuestionIndex);
            } else {
                endExam();
            }
        });

        function saveAnswer() {
            const selectedAnswer = document.querySelector('input[name="answer"]:checked');
            if (selectedAnswer) {
                userAnswers[currentQuestionIndex] = selectedAnswer.value;
            }
        }

        // Start the exam
        startTimer();
        showQuestion(currentQuestionIndex);

