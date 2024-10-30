import {registerBlockType} from '@wordpress/blocks';
import {BlockControls, InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {useEffect, useRef, useState} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {DropdownMenu, SelectControl, ToggleControl} from "@wordpress/components";

function Save({attributes}) {
    return (<div
        {...useBlockProps.save()}
        data-lap-b={attributes.sizeId}
        data-lap-b-render={attributes.autoReload ? 'auto' : 'manual'}
        className={`lap-banner ${attributes.className}`}
        style={{
            position: 'relative',
        }}
    >
        {null}
    </div>);
}

const Edit = ({attributes, setAttributes}) => {
    const [bannerSizes, setBannerSizes] = useState([]);
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const ref = useRef(null);

    useEffect(() => {
        setLoading(true);
        const cookieName = 'lap_b_s-editor';
        const sizesUrl = 'https://lap-api.com/b/sizes';
        const sizesCookie = document.cookie
            .split('; ')
            .find((row) => row.startsWith(cookieName));
        if (sizesCookie) {
            try {
                const sizes = JSON.parse(sizesCookie.split('=')[1]);
                setBannerSizes(sizes);
                setAttributes({sizeId: sizes[0].id});
                setLoading(false);
            } catch (e) {
                console.error(e);
                setError(e);
                setLoading(false);
            }
        } else {
            fetch(sizesUrl)
                .then((response) => response.json())
                .then((sizes) => {
                    setBannerSizes(sizes);
                    setAttributes({sizeId: sizes[0].id});
                    document.cookie = `${cookieName}=${JSON.stringify(sizes)}; max-age=86400; path=/`;
                })
                .catch((e) => {
                    console.error(e);
                    setError(e);
                })
                .finally(() => setLoading(false));
        }
    }, []);


    useEffect(() => {
        if (!ref.current) return;
        if (!attributes.sizeId) return;

        const sizes = bannerSizes.filter((size) => size.id === attributes.sizeId);
        if (!sizes.length) return;

        const size = sizes[0];
        const {width, height} = size;

        ref.current.style.width = `${width}px`;
        ref.current.style.height = `${height}px`;
    }, [attributes.sizeId, ref, bannerSizes])

    return (<div
        {...useBlockProps()}
        ref={ref}
        style={{
            backgroundColor: '#dcf39b',
            border: '1px solid #e5e5e5',
            borderRadius: '4px',
            transition: 'all 0.3s ease',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            position: 'relative',
            overflow: 'hidden',
            margin: 'auto',
        }}
        className={`lap-banner ${attributes.className} wp-block`} data-type={'realads'}>
        >
        <BlockControls>
            <div style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                borderLeft: '1px solid black',
                borderRight: '1px solid black',
                padding: "5px 10px",
                gap: "15px",
            }}>
                <DropdownMenu
                    style={{height: '100%'}}
                    icon={'image-crop'}
                    label={__('Banner Size', 'lap-api')}
                    controls={bannerSizes.map((size) => ({
                        title: `${size.name} (${size.width}px*${size.height}px)`,
                        onClick: () => setAttributes({sizeId: size.id}),
                        isActive: attributes.sizeId === size.id,
                    }))}
                />
                <div style={{
                    width: '1px',
                    height: '75%',
                    backgroundColor: 'black',
                }}/>
                <ToggleControl
                    label={__('Auto Reload', 'lap-api')}
                    checked={attributes.autoReload}
                    onChange={(autoReload) => setAttributes({autoReload})}
                />
            </div>
        </BlockControls>
        <InspectorControls>
            <ToggleControl
                label={__("Auto reload", 'lap-api')}
                checked={attributes.autoReload}
                onChange={(autoReload) => setAttributes({autoReload})}
            />
            <SelectControl
                label={__("Select Size", 'lap-api')}
                value={attributes.sizeId}
                options={bannerSizes.map((size) => ({
                    label: size.name, value: size.id,
                })) || []}
                onChange={(value) => setAttributes({sizeId: value})}
            />
        </InspectorControls>
        {loading ? (<div
            style={{
                width: '100%', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}
        >
            <div className="spinner"/>
        </div>) : !!error ? (<div style={{padding: '10px'}}>
            <p>{__('Error loading banner sizes', 'lap-api')}</p>
            <p>{error.message}</p>
        </div>) : (<>
            <div style={{
                display: "inline",
                textAlign: "center",
                transform: 'rotate(350deg)',
                margin: 0,
                padding: 0,
                color: 'brown',
            }}>
                <h5 style={{
                    margin: 0, userSelect: 'none',
                }}>
                    {__('Banner Placeholder', 'lap-api')}
                </h5>
            </div>
        </>)}
    </div>);
};

registerBlockType("realads/banner", {
    title: __('Banner - RealAds', 'lap-api'),
    description: __('Add a Banner to your page.', 'lap-api'),
    icon: "embed-generic",
    supports: {
        reusable: true, inserter: true, anchor: true, align: true
    },
    example: {
        attributes: {
            sizeId: "A", autoReload: true,
        }
    },
    attributes: {
        sizeId: {
            type: 'string', default: 'A',
        }, autoReload: {
            type: 'boolean', default: true,
        }
    },
    category: "realads",
    edit: Edit,
    save: Save,
});

