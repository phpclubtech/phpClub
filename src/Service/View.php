<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 4:20 PM
 */

namespace phpClub\Service;

use Slim\Http\Response;

/**
 * Basic view implementation. Can render .phtml templates into PSR-7 Response objects.
 *
 * @package phpClub\Service
 * @author foobar1643 <foobar76239@gmail.com>
 */
class View
{
    /**
     * @var string Absolute path to the templates directory.
     */
    protected $templatesDir;

    /**
     * View constructor.
     *
     * @param string $templatesDir Absolute path to the templates directory.
     *
     * @todo Strip slashes from the end of $templatesDir (if there is any).
     */
    public function __construct(string $templatesDir)
    {
        $this->templatesDir = $templatesDir;
    }

    /**
     * Renders a template with given name to PSR-7 Response object using PHP's output buffering control.
     *
     * @param Response $response
     * @param string   $template
     * @param array    $data
     *
     * @return Response
     */
    public function renderToResponse(Response $response, string $template, array $data = []): Response
    {
        $this->validateWritableResponseBody($response);

        $response->getBody()->write($this->renderToHtml($template, $data));

        return $response;
    }

    /**
     * We're not using ob_end_clean here, because Slim framework already does that for some reason.
     * If you try and uncomment that, the application will crash at some point in Slim's route resolving.
     *
     * Do note, that output buffering does not send any headers to the client, since it never sends
     * the client anything when require() function call is made, ob_end_clean() call ensures that behavior.
     *
     * @todo Investigate why Slim framework clears output buffer.
     *
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    protected function renderToHtml(string $template, array $data = []): string
    {
        $pathToTemplate = $this->getTemplateFilename($template);
        $this->validateTemplateFileExists($pathToTemplate);

        ob_start();
        extract($data, EXTR_PREFIX_SAME, "wddx");
        require($pathToTemplate);
        $template = ob_get_clean();
        // ob_end_clean();

        return $template;
    }

    /**
     * Generates full path to the template file, using the path to the templates directory that was defined
     * on object creation. File extension for the templates is hardcoded here, this is done to achieve
     * enough flexibility so we can change it easily in the future.
     *
     * Do note, that this method won't validate generated path, implementations should have their own way to do that.
     *
     * @param string $templateName Name of the template.
     *
     * @return string Full path to the template file, including the file itself, and file extension.
     */
    protected function getTemplateFilename(string $templateName): string
    {
        return "{$this->templatesDir}/{$templateName}.phtml";
    }

    /**
     * Preforms a check for writable body in given Response instance. Throws an exception on failure.
     *
     * @param Response $response
     *
     * @throws \InvalidArgumentException If given Response object body is not writable.
     *
     * @return bool True, if the body is writable.
     */
    protected function validateWritableResponseBody(Response $response): bool
    {
        if (!$response->getBody()->isWritable()) {
            throw new \InvalidArgumentException('Response body must be writable in order to render a template.');
        }

        return true;
    }

    /**
     * Preforms a check for given template file existence in the filesystem. Throws an exception on failure.
     *
     * @param string $pathToTemplate
     *
     * @throws \InvalidArgumentException If given path to template file does not exist in the filesystem.
     *
     * @todo Preform a check to see if PHP has permission to read given file, throw a different exception if it can't.
     *
     * @return bool True, if the file exists.
     */
    protected function validateTemplateFileExists(string $pathToTemplate): bool
    {
        if (!file_exists($pathToTemplate)) {
            throw new \InvalidArgumentException("Failed to load '{$pathToTemplate}'. No such file in directory.");
        }

        return true;
    }
}
