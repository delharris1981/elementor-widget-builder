import { render, useState, useEffect, useRef } from '@wordpress/element';
import CodeMirror from '@uiw/react-codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import './style.css';

const App = () => {
    const [htmlCode, setHtmlCode] = useState('<h2>Hello World</h2>\n<p>Start editing to see changes.</p>');
    const [cssCode, setCssCode] = useState('h2 { color: #2271b1; }\np { font-style: italic; }');
    const [selectedElement, setSelectedElement] = useState(null);
    const [mappings, setMappings] = useState([]);
    const [isSaving, setIsSaving] = useState(false);
    const [postId, setPostId] = useState(0);
    const iframeRef = useRef(null);

    const updatePreview = () => {
        const iframe = iframeRef.current;
        if (!iframe) return;

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        const selectionScript = `
            <script>
                (function() {
                    let hoveredElement = null;
                    let selectedElement = null;

                    document.addEventListener('mouseover', function(e) {
                        e.stopPropagation();
                        if (hoveredElement) hoveredElement.style.outline = '';
                        hoveredElement = e.target;
                        hoveredElement.style.outline = '2px dashed #2271b1';
                        hoveredElement.style.cursor = 'pointer';
                    });

                    document.addEventListener('mouseout', function(e) {
                        if (hoveredElement) hoveredElement.style.outline = '';
                        hoveredElement = null;
                    });

                    document.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (selectedElement) selectedElement.classList.remove('ehb-selected');
                        selectedElement = e.target;
                        selectedElement.classList.add('ehb-selected');
                        
                        // Send message to parent
                        window.parent.postMessage({
                            type: 'EHB_ELEMENT_SELECTED',
                            selector: getUniqueSelector(selectedElement),
                            tagName: selectedElement.tagName,
                            content: selectedElement.innerText
                        }, '*');
                    });

                    function getUniqueSelector(el) {
                        if (el.id) return '#' + el.id;
                        if (el === document.body) return 'body';
                        
                        let path = [];
                        while (el.parentElement) {
                            let index = Array.from(el.parentElement.children).indexOf(el) + 1;
                            path.unshift(el.tagName.toLowerCase() + ':nth-child(' + index + ')');
                            el = el.parentElement;
                        }
                        return path.join(' > ');
                    }
                })();
            </script>
        `;

        let h = `
            <!DOCTYPE html>
            <html>
            <head>
                <style>_CSS_CODE_</style>
                <style>
                    .ehb-selected { outline: 2px solid #2271b1 !important; }
                </style>
            </head>
            <body>
                _HTML_CODE_
                _SELECTION_SCRIPT_
            </body>
            </html>
        `;

        h = h.replace('_CSS_CODE_', cssCode);
        h = h.replace('_HTML_CODE_', htmlCode);
        h = h.replace('_SELECTION_SCRIPT_', selectionScript);

        doc.open();
        doc.write(h);
        doc.close();
    };

    useEffect(() => {
        updatePreview();

        const handleMessage = (event) => {
            if (event.data.type === 'EHB_ELEMENT_SELECTED') {
                setSelectedElement(event.data);
            }
        };

        window.addEventListener('message', handleMessage);
        return () => window.removeEventListener('message', handleMessage);
    }, [htmlCode, cssCode]);

    const addMapping = (controlType) => {
        if (!selectedElement) return;

        const newMapping = {
            id: Date.now(),
            selector: selectedElement.selector,
            tagName: selectedElement.tagName,
            controlType: controlType,
            controlId: `control_${mappings.length + 1}`
        };

        setMappings([...mappings, newMapping]);
    };

    const saveWidget = async () => {
        setIsSaving(true);

        const data = new FormData();
        data.append('action', 'ehb_save_widget');
        data.append('nonce', window.ehbBuilderData.nonce);
        data.append('post_id', postId);
        data.append('html', htmlCode);
        data.append('css', cssCode);
        data.append('mappings', JSON.stringify(mappings));

        try {
            const response = await fetch(window.ehbBuilderData.ajaxUrl, {
                method: 'POST',
                body: data
            });
            const result = await response.json();

            if (result.success) {
                setPostId(result.data.post_id);
                alert(result.data.message);
            } else {
                alert('Error: ' + result.data);
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Failed to save widget.');
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="ehb-builder">
            <div className="ehb-main-area">
                <div className="ehb-editor-panels">
                    <div className="ehb-panel">
                        <div className="ehb-panel-header">HTML</div>
                        <CodeMirror
                            value={htmlCode}
                            height="100%"
                            extensions={[html()]}
                            onChange={(value) => setHtmlCode(value)}
                            theme="dark"
                        />
                    </div>
                    <div className="ehb-panel">
                        <div className="ehb-panel-header">CSS</div>
                        <CodeMirror
                            value={cssCode}
                            height="100%"
                            extensions={[css()]}
                            onChange={(value) => setCssCode(value)}
                            theme="dark"
                        />
                    </div>
                </div>

                <div className="ehb-preview-container">
                    <div className="ehb-preview-header">
                        <h3>Live Preview</h3>
                        <div className="ehb-actions">
                            <button className="ehb-render-button" onClick={updatePreview}>
                                Render Preview
                            </button>
                            <button
                                className="ehb-save-button"
                                onClick={saveWidget}
                                disabled={isSaving}
                            >
                                {isSaving ? 'Saving...' : 'Save Widget'}
                            </button>
                        </div>
                    </div>
                    <div className="ehb-preview-frame-container">
                        <iframe
                            id="ehb-preview-frame"
                            ref={iframeRef}
                            title="Preview"
                        />
                    </div>
                </div>
            </div>

            <div className="ehb-sidebar">
                <div className="ehb-sidebar-header">Mappings</div>
                <div className="ehb-sidebar-content">
                    {selectedElement ? (
                        <div className="ehb-selection-info">
                            <h4>Selected: {selectedElement.tagName}</h4>
                            <code>{selectedElement.selector}</code>
                            <div className="ehb-control-options">
                                <p>Map to Control:</p>
                                <button onClick={() => addMapping('text')}>Text</button>
                                <button onClick={() => addMapping('media')}>Image</button>
                                <button onClick={() => addMapping('color')}>Color</button>
                            </div>
                        </div>
                    ) : (
                        <p className="ehb-no-selection">Click an element in the preview to map it.</p>
                    )}

                    <div className="ehb-mappings-list">
                        <h4>Active Mappings</h4>
                        {mappings.length === 0 ? <p>No mappings yet.</p> : (
                            <ul>
                                {mappings.map(m => (
                                    <li key={m.id}>
                                        <strong>{m.controlId}</strong>: {m.controlType} ({m.tagName})
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

const root = document.getElementById('ehb-builder-root');
if (root) {
    render(<App />, root);
}
