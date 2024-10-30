import {registerBlockType} from '@wordpress/blocks';
import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {__} from '@wordpress/i18n';
import {useEffect, useState} from "@wordpress/element";
import {TextControl} from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";

function Save({attributes}) {
    return (
        <div
            {...useBlockProps.save()}
            className={`lap-banner ${attributes.className}`}
            data-lap-n=''
            data-lap-n-container={attributes.container}
            data-lap-n-text-container={attributes.textContainer}
            data-lap-n-title={attributes.title}
            data-lap-n-content={attributes.content}
            data-lap-n-image={attributes.image}
            style={{position: 'relative',}}
        />
    );
}

function Edit({attributes, setAttributes}) {
    const [container, setContainer] = useState(attributes.container);
    const [textContainer, setTextContainer] = useState(attributes.textContainer);
    const [title, setTitle] = useState(attributes.title);
    const [content, setContent] = useState(attributes.content);
    const [image, setImage] = useState(attributes.image);

    useEffect(() => {
        apiFetch({path: 'lap-api/v1/options'}).then(options => {
            if (!options.native_ad_default_classes) return;
            if (!!options.native_ad_default_classes.container) setContainer(options.native_ad_default_classes.container);
            if (!!options.native_ad_default_classes["text-container"]) setTextContainer(options.native_ad_default_classes["text-container"]);
            if (!!options.native_ad_default_classes.title) setTitle(options.native_ad_default_classes.title);
            if (!!options.native_ad_default_classes.content) setContent(options.native_ad_default_classes.content);
            if (!!options.native_ad_default_classes.image) setImage(options.native_ad_default_classes.image);
        });
    }, [])


    useEffect(() => {
        setAttributes({
            container,
            textContainer,
            title,
            content,
            image,
        });
    }, [container, textContainer, title, content, image]);

    const textControlStyle = {
        fontFamily: "monospace",
        fontSize: "12px",
        margin: "8px",
        width: "90%",
    }

    return (
        <div style={{
            margin: "auto",
            width: "fit-content",
            transition: "all 0.5s ease",
        }} className="wp-block" data-type={'realads'}>
            <InspectorControls>
                <TextControl label="Container" value={container} onChange={setContainer}
                             style={textControlStyle}/>
                <TextControl label="Text Container" value={textContainer} onChange={setTextContainer}
                             style={textControlStyle}/>
                <TextControl label="Title" value={title} onChange={setTitle} style={textControlStyle}/>
                <TextControl label="Content" value={content} onChange={setContent} style={textControlStyle}/>
                <TextControl label="Image" value={image} onChange={setImage} style={textControlStyle}/>
            </InspectorControls>
            <div style={{
                textAlign: "center",
                padding: "16px",
            }}>
                <h5>
                    {__("Native Ad - RealAds", "lap-api")}
                </h5>
                <p>
                    {__("Enter your custom classes in the side bar (Inspector). This is optional, you can always use the default classes given by RealAds.", "lap-api")}
                </p>
            </div>


            <div className={`${attributes.className} ${attributes.container}`} style={{
                border: "1px solid #ccc",
            }}>
                <div className={attributes.textContainer}>
                    <h3 className={attributes.title}>
                        {__("Title Example", "lap-api")}
                    </h3>
                    <p className={attributes.content}>
                        {__("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tem", "lap-api")}
                    </p>
                </div>
                <img className={attributes.image} src="https://via.placeholder.com/150" alt={"Image example"}/>
            </div>
        </div>
    );
}


registerBlockType('realads/native', {
    title: __('Native Ad - RealAds', 'lap-api'),
    icon: 'megaphone',
    description: __('Native Ad - RealAds', 'lap-api'),
    category: 'realads',
    supports: {
        reusable: true,
        inserter: true,
        anchor: true,
        align: true
    },
    attributes: {
        container: {
            type: 'string',
            default: ''
        },
        textContainer: {
            type: 'string',
            default: ''
        },
        title: {
            type: 'string',
            default: ''
        },
        content: {
            type: 'string',
            default: ''
        },
        image: {
            type: 'string',
            default: ''
        }
    },
    edit: Edit,
    save: Save,
});
