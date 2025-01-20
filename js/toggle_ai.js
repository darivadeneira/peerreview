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
    var instructionsContent = feedbackData.instructions[instructionKey].instructauthors;
    var messages = [];

    // Crear un array de objetos con las rúbricas
    var rubrics = [];
    for (var rubricKey in feedbackData.rubrics) {
        if (feedbackData.rubrics.hasOwnProperty(rubricKey)) {
            var rubric = feedbackData.rubrics[rubricKey];
            rubrics.push({
                definition: rubric.rubric_definition,
                grade: rubric.rubric_grade
            });
        }
    }

    // Obtener las filas de la tabla
    var tableRows = table.tBodies[0].rows;
    
    // Iterar sobre las filas de la tabla para mantener el orden
    for (let i = 0; i < tableRows.length; i++) {
        var row = tableRows[i];
        // Obtener el ID del autor de la segunda columna (índice 1)
        var studentId = row.cells[1].textContent.trim();
        console.log("ID del estudiante encontrado:", studentId);
        var grade = null;

        // Buscar la calificación correspondiente a esta fila
        for (var gradeKey in feedbackData.grades) {
            if (feedbackData.grades.hasOwnProperty(gradeKey)) {
                // Convertir ambos valores a string para comparación
                if (String(feedbackData.grades[gradeKey].student_id) === String(studentId)) {
                    grade = feedbackData.grades[gradeKey];
                    break;
                }
            }
        }

        if (grade) {
            // Construir el string de rúbricas
            var rubricsText = rubrics.map((rubric, index) => 
                `Rúbrica ${index + 1} definición: ${rubric.definition}
                Rúbrica ${index + 1} grade: ${rubric.grade}`
            ).join('\n                ');

            // Preparar el contenido con el nuevo formato
            var content = `
                Indicaciones a la IA: Evalúa si la respuesta del estudiante es adecuada según la rúbrica.
                Tema de la evaluación: ${instructionsContent}.
                ${rubricsText}
                Respuesta del estudiante: ${grade.student_content}.
                Rúbrica asignada: ${grade.rubric_definition}.
                Retroalimentación supervisor: ${grade.feedback_author}.
                `;

            messages.push({
                role: "user",
                content: content,
                rowIndex: i  // Guardamos el índice de la fila para referencia
            });
        } else {
            console.log("No se encontró calificación para el estudiante:", studentId);
        }
    }

    console.log("Mensajes a enviar:", messages);

    // Manejar clic en el botón "Revisar ahora"
    reviewButton.addEventListener("click", async function (event) {
      event.preventDefault();
      var apiKey = apiKeyInput.value;

      if (!apiKey) {
        alert("Por favor, ingrese la API Key antes de enviar.");
        return;
      }

      localStorage.setItem("apiKey", apiKey);

      // Array para almacenar los resultados
      let resultsToSave = [];

      // Procesar cada mensaje individualmente
      for (let i = 0; i < messages.length; i++) {
        try {
          const row = table.tBodies[0].rows[messages[i].rowIndex];
          const assessmentId = row.cells[0].textContent.trim(); // Obtener assessment ID de la primera columna

          const response = await fetch("https://api.openai.com/v1/chat/completions", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${apiKey}`,
            },
            body: JSON.stringify({
              model: "gpt-4o-mini",
              messages: [
                {
                  role: "system",
                  content: "Evalúa si la respuesta del estudiante es adecuada según la rúbrica proporcionada. Responde únicamente con 'Sin Novedad' o 'Revisión'. ",
                },
                messages[i]  // Enviamos solo un mensaje por petición
              ],
              temperature: 0.5,
              max_tokens: 1000,
            }),
          });

          if (!response.ok) {
            throw new Error(`Error en la solicitud: ${response.statusText}`);
          }

          const data = await response.json();

          if (data.choices && data.choices[0].message) {
            const aiResponse = data.choices[0].message.content;
            console.log(`Evaluación ${i + 1} exitosa:`, data);
            
            // Actualizar la celda en la tabla
            var iaCell = row.querySelector('.ia_data');
            if (iaCell) {
              iaCell.textContent = aiResponse;
            }

            // Agregar resultado al array
            resultsToSave.push({
              assesmentid: assessmentId,
              feedback_ai: aiResponse
            });
          }

        } catch (error) {
          console.error(`Error en el procesamiento de la solicitud ${i + 1}:`, error);
        }
      }

      // Enviar todos los resultados al servidor
      if (resultsToSave.length > 0) {
        try {
          const formData = new FormData();
          formData.append('feedbackdata', JSON.stringify(resultsToSave));

          const saveResponse = await fetch('eval/peerreview/evaluate_feedback_ai.php', {
            method: 'POST',
            body: formData
          });

          const responseText = await saveResponse.text();
          console.log('Respuesta del servidor:', responseText);

          try {
            const saveResult = JSON.parse(responseText);
            if (saveResult.status === 'success') {
              alert('Resultados guardados correctamente');
            } else {
              throw new Error(saveResult.message);
            }
          } catch (parseError) {
            console.error('Error al parsear la respuesta:', responseText);
            throw new Error('Error en el formato de respuesta del servidor');
          }
        } catch (error) {
          console.error('Error al guardar los resultados:', error);
          alert('Error al guardar los resultados: ' + error.message);
        }
      }
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
