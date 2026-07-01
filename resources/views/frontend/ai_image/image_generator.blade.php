<div class="widget friend-widget">
    <div class="n_pro_con d-flex align-items-start">
        <div class="demo-badge">
            <h4>{{get_phrase('Text-to-Image Generator')}}</h4>
        </div>
    </div>

    <form id="text-form">
        <label for="input-text" class="widget-title">{{get_phrase('Enter your text:')}}</label>
        <input type="text" id="input-text" placeholder="Type something..." required>
        <button type="submit" class="btn common mt-3 rounded w-100 btn-lg active">{{get_phrase('Generate Image')}}</button>
    </form>
    <div class="output ai_image_generate_img">
        <img id="generated-image" src="" alt="Generated Image" class="hidden">
        <a id="download-button" class="hidden btn common mt-3 rounded w-100 btn-lg active" download="generated-image.png"><i class="fa-solid fa-download"></i> {{get_phrase('Download Image')}} </a>
    </div>
</div>


<script>
    const token = "{{ $hugging_face_auth_key }}"
    const form = document.getElementById('text-form');
    const inputText = document.getElementById('input-text');
    const outputImage = document.getElementById('generated-image');
    const downloadButton = document.getElementById('download-button');
    
    // Function to fetch image with retry logic
    async function fetchImageWithRetry(text, retries = 3, delay = 5000) {
        for (let i = 0; i < retries; i++) {
            try {
                outputImage.src = "{{asset('assets/frontend/images/loader.gif')}}";
                outputImage.classList.remove('hidden'); // Ensure the image is visible during loading
            
                const response = await fetch('https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-2', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ inputs: text }),
                });
    
                if (response.ok) {
                    return await response.blob(); // Return image blob
                } else {
                    const errorDetails = await response.json();
                    console.error("Error details:", errorDetails);
                    if (errorDetails.error && errorDetails.error.includes("currently loading")) {
                        console.log(`Retrying... (${i + 1}/${retries})`);
                    } else {
                        throw new Error(errorDetails.error || response.statusText);
                    }
                }
            } catch (error) {
                if (i === retries - 1) {
                    throw error; // Re-throw error if retries are exhausted
                }
                console.log(`Waiting ${delay / 1000} seconds before retrying...`);
                await new Promise((resolve) => setTimeout(resolve, delay)); // Wait before retrying
            }
        }
    }
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
    
        const text = inputText.value.trim();
        if (!text) {
            alert("Please enter valid text.");
            return;
        }
    
        // Reset output
        outputImage.classList.add('hidden');
        downloadButton.classList.add('hidden');
        outputImage.alt = "Generating...";
    
        try {
            const imageBlob = await fetchImageWithRetry(text); // Fetch image with retry
            const imageUrl = URL.createObjectURL(imageBlob);
    
            // Display the generated image
            outputImage.src = imageUrl;
            outputImage.classList.remove('hidden');
            outputImage.alt = "Generated Image";
    
            // Enable the download button
            downloadButton.href = imageUrl;
            downloadButton.classList.remove('hidden');
        } catch (error) {
            console.error("Error occurred:", error);
            alert(`An error occurred: ${error.message}`);
        }
    });
    
    
</script>