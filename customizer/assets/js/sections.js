const sectionSettings = {
    heading: {
        name: 'כותרת',
        settings: {
            content: {
                type: 'input',
                label: 'תוכן הכותרת',
                default: 'כותרת'
            },
            typography: {
                type: 'accordion',
                label: 'טיפוגרפיה',
                icon: 'ri-text',
                settings: {
                    fontFamily: {
                        type: 'select',
                        label: 'סוג פונט',
                        options: ['Arial', 'Helvetica', 'Times New Roman', 'Georgia']
                    },
                    fontSize: {
                        type: 'number',
                        label: 'גודל טקסט',
                        default: 24
                    },
                    fontWeight: {
                        type: 'select',
                        label: 'עובי טקסט',
                        options: ['normal', 'bold', 'lighter']
                    },
                    textDecoration: {
                        type: 'select',
                        label: 'קו תחתון',
                        options: ['none', 'underline', 'line-through']
                    }
                }
            },
            design: {
                type: 'accordion',
                label: 'עיצוב',
                icon: 'ri-palette',
                settings: {
                    textColor: {
                        type: 'color',
                        label: 'צבע טקסט',
                        default: '#000000'
                    },
                    backgroundColor: {
                        type: 'color',
                        label: 'צבע רקע',
                        default: '#ffffff'
                    },
                    textAlign: {
                        type: 'select',
                        label: 'יישור טקסט',
                        options: ['right', 'center', 'left']
                    }
                }
            }
        }
    },
    text: {
        name: 'טקסט',
        settings: {
            content: {
                type: 'textarea',
                label: 'תוכן הטקסט',
                default: 'הכנס טקסט כאן'
            },
            typography: {
                type: 'accordion',
                label: 'טיפוגרפיה',
                icon: 'ri-text',
                settings: {
                    fontFamily: {
                        type: 'select',
                        label: 'סוג פונט',
                        options: ['Arial', 'Helvetica', 'Times New Roman', 'Georgia']
                    },
                    fontSize: {
                        type: 'number',
                        label: 'גודל טקסט',
                        default: 16
                    },
                    fontWeight: {
                        type: 'select',
                        label: 'עובי טקסט',
                        options: ['normal', 'bold', 'lighter']
                    },
                    textDecoration: {
                        type: 'select',
                        label: 'קו תחתון',
                        options: ['none', 'underline', 'line-through']
                    }
                }
            },
            design: {
                type: 'accordion',
                label: 'עיצוב',
                icon: 'ri-palette',
                settings: {
                    textColor: {
                        type: 'color',
                        label: 'צבע טקסט',
                        default: '#000000'
                    },
                    backgroundColor: {
                        type: 'color',
                        label: 'צבע רקע',
                        default: '#ffffff'
                    },
                    textAlign: {
                        type: 'select',
                        label: 'יישור טקסט',
                        options: ['right', 'center', 'left']
                    }
                }
            }
        }
    },
    image: {
        name: 'תמונה',
        settings: {
            src: {
                type: 'input',
                label: 'כתובת התמונה',
                default: ''
            },
            alt: {
                type: 'input',
                label: 'טקסט חלופי',
                default: ''
            },
            preview: {
                type: 'image-preview',
                width: 200
            },
            upload: {
                type: 'button',
                label: 'העלאת תמונה'
            },
            design: {
                type: 'accordion',
                label: 'עיצוב',
                icon: 'ri-palette',
                settings: {
                    width: {
                        type: 'select',
                        label: 'רוחב התמונה',
                        options: ['25%', '50%', '75%', '100%']
                    },
                    backgroundColor: {
                        type: 'color',
                        label: 'צבע רקע',
                        default: '#ffffff'
                    },
                    align: {
                        type: 'select',
                        label: 'יישור תמונה',
                        options: ['right', 'center', 'left']
                    }
                }
            }
        }
    },
    button: {
        name: 'כפתור',
        settings: {
            content: {
                type: 'input',
                label: 'טקסט הכפתור',
                default: 'לחץ כאן'
            },
            typography: {
                type: 'accordion',
                label: 'טיפוגרפיה',
                icon: 'ri-text',
                settings: {
                    fontFamily: {
                        type: 'select',
                        label: 'סוג פונט',
                        options: ['Arial', 'Helvetica', 'Times New Roman', 'Georgia']
                    },
                    fontSize: {
                        type: 'number',
                        label: 'גודל טקסט',
                        default: 16
                    },
                    fontWeight: {
                        type: 'select',
                        label: 'עובי טקסט',
                        options: ['normal', 'bold', 'lighter']
                    },
                    textDecoration: {
                        type: 'select',
                        label: 'קו תחתון',
                        options: ['none', 'underline', 'line-through']
                    }
                }
            },
            design: {
                type: 'accordion',
                label: 'עיצוב',
                icon: 'ri-palette',
                settings: {
                    textColor: {
                        type: 'color',
                        label: 'צבע טקסט',
                        default: '#ffffff'
                    },
                    backgroundColor: {
                        type: 'color',
                        label: 'צבע רקע',
                        default: '#007bff'
                    },
                    textAlign: {
                        type: 'select',
                        label: 'יישור טקסט',
                        options: ['right', 'center', 'left']
                    },
                    fullWidth: {
                        type: 'checkbox',
                        label: 'רוחב מלא',
                        default: false
                    }
                }
            }
        }
    },
    divider: {
        name: 'קו מפריד',
        settings: {
            design: {
                type: 'accordion',
                label: 'עיצוב',
                icon: 'ri-palette',
                settings: {
                    color: {
                        type: 'color',
                        label: 'צבע קו',
                        default: '#000000'
                    },
                    width: {
                        type: 'select',
                        label: 'רוחב קו',
                        options: ['50%', '100%'],
                        default: '100%'
                    }
                }
            }
        }
    },
    social: {
        name: 'רשתות חברתיות',
        settings: {
            facebook: {
                type: 'input',
                label: 'פייסבוק',
                default: ''
            },
            instagram: {
                type: 'input',
                label: 'אינסטגרם',
                default: ''
            },
            twitter: {
                type: 'input',
                label: 'טוויטר',
                default: ''
            },
            tiktok: {
                type: 'input',
                label: 'טיקטוק',
                default: ''
            },
            email: {
                type: 'input',
                label: 'אימייל',
                default: ''
            },
            whatsapp: {
                type: 'input',
                label: 'וואטסאפ',
                default: ''
            },
            typography: {
                type: 'accordion',
                label: 'טיפוגרפיה',
                icon: 'ri-text',
                settings: {
                    fontFamily: {
                        type: 'select',
                        label: 'סוג פונט',
                        options: ['Arial', 'Helvetica', 'Times New Roman', 'Georgia']
                    },
                    fontSize: {
                        type: 'number',
                        label: 'גודל טקסט',
                        default: 16
                    },
                    fontWeight: {
                        type: 'select',
                        label: 'עובי טקסט',
                        options: ['normal', 'bold', 'lighter']
                    },
                    textDecoration: {
                        type: 'select',
                        label: 'קו תחתון',
                        options: ['none', 'underline', 'line-through']
                    }
                }
            },
            design: {
                type: 'accordion',
                label: 'עיצוב',
                icon: 'ri-palette',
                settings: {
                    iconColor: {
                        type: 'color',
                        label: 'צבע אייקון',
                        default: '#000000'
                    },
                    textColor: {
                        type: 'color',
                        label: 'צבע טקסט',
                        default: '#000000'
                    },
                    backgroundColor: {
                        type: 'color',
                        label: 'צבע רקע',
                        default: '#ffffff'
                    },
                    textAlign: {
                        type: 'select',
                        label: 'יישור טקסט',
                        options: ['right', 'center', 'left']
                    },
                    iconSize: {
                        type: 'select',
                        label: 'גודל אייקון',
                        options: ['קטן', 'בינוני', 'ענק'],
                        default: 'בינוני'
                    }
                }
            }
        }
    }
};

export default sectionSettings;