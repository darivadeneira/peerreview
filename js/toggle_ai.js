document.addEventListener("DOMContentLoaded", function () {
  var toggleAiButton = document.getElementById("toggle-ai-button");
  var otherButton = document.getElementById("id_submit");
  var title = document.getElementById("id_general");
  var table = document.getElementById("feedback-table");
  var ia_header = document.getElementById("ia_header");
  var ia_data = document.getElementsByClassName("ia_data");

  // Asegurarte de que 'feedbackData' está disponible antes de usarla
  if (typeof feedbackData === "undefined") {
    console.error("Los datos de retroalimentación no están disponibles.");
    return;
  }
  console.log("Datos de retroalimentación:", feedbackData);

  if (toggleAiButton && otherButton) {
    otherButton.style.display = "none";
    title.style.display = "none";

    // Crear un contenedor para los inputs y los botones
    var container = document.createElement("div");
    container.id = "ai-input-container";
    container.style.display = "flex";
    container.style.alignItems = "center";
    container.style.gap = "10px"; // Espaciado entre los elementos

    // Crear el campo de texto para la API Key
    var apiKeyInput = document.createElement("input");
    apiKeyInput.type = "text";
    apiKeyInput.id = "api-key-input";
    apiKeyInput.placeholder = "Ingrese su API Key";
    apiKeyInput.className = "form-control";

    // Crear el botón "Revisar ahora"
    var reviewButton = document.createElement("button");
    reviewButton.id = "review-button";
    reviewButton.textContent = "Revisar ahora";
    reviewButton.className = "btn btn-success";

    // Insertar el contenedor y añadir los elementos
    toggleAiButton.parentNode.insertBefore(container, toggleAiButton);
    container.appendChild(toggleAiButton);
    container.appendChild(apiKeyInput);
    container.appendChild(reviewButton);

    // Comprobar si la IA está activada al cargar la página
    if (localStorage.getItem("aiActivated") === "true") {
      toggleAiButton.textContent = "Desactivar revisión por IA";
      toggleAiButton.classList.remove("btn-primary");
      toggleAiButton.classList.add("btn-danger");
      apiKeyInput.style.display = "inline-block";
      reviewButton.style.display = "inline-block";

      // Mostrar la columna de "Revisión IA" en la tabla
      addAiColumnToTable();
    } else {
      toggleAiButton.textContent = "Activar revisión por IA";
      toggleAiButton.classList.remove("btn-danger");
      toggleAiButton.classList.add("btn-primary");
      apiKeyInput.style.display = "none";
      reviewButton.style.display = "none";

      // Eliminar la columna de "Revisión IA" en la tabla si no está activada
      removeAiColumnFromTable();
    }

    // Restaurar los valores de la API Key y URL si existen
    apiKeyInput.value = localStorage.getItem("apiKey") || "";

    // Manejar clic en el botón para mostrar/ocultar elementos
    toggleAiButton.addEventListener("click", function () {
      var button = document.getElementById("toggle-ai-button");
      var headerRow = table.tHead.rows[0];
      var rows = table.tBodies[0].rows;

      if (button.classList.contains("btn-primary")) {
        // Mostrar inputs y botón
        apiKeyInput.style.display = "inline-block";
        apiKeyInput.type = "password";
        reviewButton.style.display = "inline-block";

        // Añadir la columna "Revisión IA" en la tabla
        addAiColumnToTable();

        // Cambiar botón a "Desactivar IA"
        button.textContent = "Desactivar revisión por IA";
        button.classList.remove("btn-primary");
        button.classList.add("btn-danger");

        // Guardar el estado en localStorage
        localStorage.setItem("aiActivated", "true");
      } else {
        // Ocultar inputs y botón
        apiKeyInput.style.display = "none";
        reviewButton.style.display = "none";

        // Eliminar la columna "Revisión IA" de la tabla
        removeAiColumnFromTable();

        // Cambiar botón a "Activar IA"
        button.textContent = "Activar revisión por IA";
        button.classList.remove("btn-danger");
        button.classList.add("btn-primary");

        // Guardar el estado en localStorage
        localStorage.setItem("aiActivated", "false");
      }
    });

    var instructionKey = Object.keys(feedbackData.instructions)[0];
    var instructionsContent =
      feedbackData.instructions[instructionKey].instructauthors;
    var messages = [];

    var allRubricDefinitions = [];
    var allRubricGrades = [];

    for (var rubricKey in feedbackData.rubrics) {
      if (feedbackData.rubrics.hasOwnProperty(rubricKey)) {
        var rubric = feedbackData.rubrics[rubricKey];
        allRubricDefinitions.push(rubric.rubric_definition);
        allRubricGrades.push(rubric.rubric_grade);
      }
    }

    for (var gradeKey in feedbackData.grades) {
      if (feedbackData.grades.hasOwnProperty(gradeKey)) {
        var grade = feedbackData.grades[gradeKey];

        // Preparar el contenido incluyendo todas las rúbricas
        var content = `
                Indicaciones a la IA: Evalúa si la respuesta del estudiante es adecuada según la rúbrica.
                Tema de la evaluación: ${instructionsContent}.
                Rúbrica definición: ${allRubricDefinitions.join(" - ")}.
                Rúbrica grade: ${allRubricGrades.join(" - ")}.
                Respuesta del estudiante: ${grade.student_content}.
                Rúbrica asignada: ${grade.rubric_definition}.
                Retroalimentación supervisor: ${grade.feedback_author}.
                `;

        // Agregar el mensaje al array de mensajes
        messages.push({
          role: "user",
          content: content,
        });
      }
    }
    console.log("Mensajes a enviar:", messages);

    // Manejar clic en el botón "Revisar ahora"
    reviewButton.addEventListener("click", function (event) {
      event.preventDefault(); // Prevenir redirección
      var apiKey = apiKeyInput.value;

      if (!apiKey) {
        alert("Por favor, ingrese la API Key antes de enviar.");
        event.preventDefault(); // Prevenir redirección
        return;
      }

      // Guardar los valores de la API Key
      localStorage.setItem("apiKey", apiKey);

      // Preparamos los datos para enviar a la API de OpenAI

      // Realizar la solicitud a la API de OpenAI
      fetch("https://api.openai.com/v1/chat/completions", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${apiKey}`, // Sustituye con tu API Key de OpenAI
        },
        body: JSON.stringify({
          model: "gpt-4o-mini",
          messages: [
            {
              role: "system",
              content:
                "Evalúa si la respuesta del estudiante es adecuada según la rúbrica proporcionada. Responde únicamente con 'Sin Novedad' o 'Revisión'. ",
            },
            ...messages,
          ],
          temperature: 0.5,
          max_tokens: 1000,
        }),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`Error en la solicitud: ${response.statusText}`);
          }
          return response.json();
        })
        .then((data) => {
          if (data.choices && data.choices[0].message) {
            console.log("Evaluación exitosa:", data);
            // Procesar y mostrar la respuesta de la IA
            for (var i = 0; i < data.choices.length; i++) {
              // Actualizar la columna de "Revisión IA" en la tabla
              var cell = rows[gradeIndex].insertCell(-1);
              cell.className = "ia_data";
              cell.textContent = message;
            }
          } else {
            console.error("Error en la evaluación:", data.error);
          }
        })
        .catch((error) => {
          console.error("Error en el procesamiento de la solicitud:", error);
        });
    });
  }

  function addAiColumnToTable() {
    for (var i = 0; i < ia_data.length; i++) {
      ia_data[i].style.display = "inline-block";
    }
    ia_header.style.display = "inline-block";
  }

  function removeAiColumnFromTable() {
    for (var i = 0; i < ia_data.length; i++) {
      ia_data[i].style.display = "none";
    }
    ia_header.style.display = "none";
  }
});
