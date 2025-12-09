<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vite + React + TS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/regular.min.css" integrity="sha512-x3gns+l9p4mIK7vYLOCUoFS2P1gavFvnO9Its8sr0AkUk46bgf9R51D8xeRUwCSk+W93YbXWi19BYzXDNBH5SA==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha512-SfTiTlX6kk+qitfevl/7LibUOeJWlt9rbyDn92a1DqWOw9vWG2MFoays0sgObmWazO5BQPiFucnnEAjpAB+/Sw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script type="module" crossorigin src="/assets/main.f6a15d3c.js"></script>
    <link rel="stylesheet" href="/assets/index.f5ec82b4.css">
  </head>
  <body>
    <div id="root"></div>
    
    <script>
      if (!localStorage.getItem('codeSnippetsRefreshed')) {
        document.addEventListener('DOMContentLoaded', () => {
          setTimeout(() => {
            const codeSnippets = document.querySelectorAll('.code-snippet');
            if (codeSnippets) {
              codeSnippets.forEach((snippet) => {
                if (snippet.textContent?.includes('[object Object]')) {
                  localStorage.setItem('codeSnippetsRefreshed', true);
                  location.reload();
                  return;
                }
              });
            } else {
              const snippets = document.querySelectorAll('pre > code');
              snippets.forEach((snippet) => {
                if (snippet.textContent?.includes('[object Object]')) {
                  localStorage.setItem('codeSnippetsRefreshed', true);
                  location.reload();
                  return;
                }
              });
            }
          }, 250);
        });
      }
    </script>
  </body>
</html>
