#!/usr/bin/perl

=encoding utf8

=head1 NAME

dtabfpp.pl -- pretty printing for DTABf documents.

=head1 SYNOPSIS

dtabfpp.pl < INFILE > OUTFILE

=head1 DESCRIPTION

Pretty printing of a DTABF XML file, according to rules which are
specified for L<XML::LibXML::PrettyPrint>.

C<< <lb/> >> elements are put on the end of each line.

=head1 REQUIREMENTS

=over 8

=item L<XML::LibXML>

=item L<XML::LibXML::PrettyPrint>

=back

=head1 TODO

Almost everything.

=head1 SEE ALSO

=over 8

=item DTABf documentation

L<http://www.deutschestextarchiv.de/doku/basisformat/uebersichtHeader.html>,
L<http://www.deutschestextarchiv.de/doku/basisformat/uebersichtText.html>.

=item L<XML::LibXML::PrettyPrint>

=back

=head1 AUTHOR

Frank Wiegand, C<< <wiegand at bbaw.de> >>, 2019.

=cut

use warnings;
use 5.012;
use XML::LibXML::PrettyPrint;

# CAVEAT: The following HoAs are merged into a single structure.
# For conflicting entries please modify the corresponding callback subs.

# elements within <teiHeader>
my $element_header = {
    inline   => [qw(choice abbr expan address bibl biblScope country date docDate edition email foreign gap idno language orgName pubPlace publisher ref title)],
    block    => [qw()],
    compact  => [qw(addName classCode forename measure rendition surname head)],
    preserves_whitespace => [qw()],
};

# (additional) elements within <text>
my $element_text = {
    inline   => [qw(hi orgName persName placeName roleName)],
    block    => [qw()],
    compact  => [qw()],
    preserves_whitespace => [qw()],
};

my $cb_inline = sub {
    my $node = shift;

    # inline elements within <teiHeader>
    if ( $node->nodeName =~ /^(?:)$/ ) {
        my $parent = $node->parentNode;
        while ( $parent ) {
            if ( $parent->nodeName eq 'teiHeader' ) {
                return 1;
            }
            $parent = $parent->parentNode;
        }
    }

    # inline elements within <text>
    if ( $node->nodeName =~ /^(?:note)$/ ) {
        my $parent = $node->parentNode;
        while ( $parent ) {
            # not sure why we can end up at '#document-fragment' instead of getting up to 'text'
            if ( $parent->nodeName eq 'text' or $parent->nodeName eq '#document-fragment') {
                return 1;
            }
            $parent = $parent->parentNode;
        }
    }

    # everything else
    return undef;
};

my $cb_block = sub {
    my $node = shift;
    # format <note> as block when it is child of <notesStmt>
    if ( $node->nodeName =~ /^(?:note)$/
          and $node->parentNode->nodeName eq 'notesStmt' )
    {
        return 1;
    }
    # format <bibl> as block when it is child of <sourceDesc>
    if ( $node->nodeName =~ /^(?:bibl)$/
          and $node->parentNode->nodeName eq 'sourceDesc' )
    {
        return 1;
    }
    # format <publisher|idno> as block when it is child of <publicationStmt>
    if ( $node->nodeName =~ /^(?:publisher|idno)$/
          and $node->parentNode->nodeName eq 'publicationStmt' )
    {
        return 1;
    }
    # block elements within <text>
    if ( $node->nodeName =~ /^(?:)$/ ) {
        my $parent = $node->parentNode;
        while ( $parent ) {
            # not sure why we can end up at '#document-fragment' instead of getting up to 'text'
            if ( $parent->nodeName eq 'text' or $parent->nodeName eq '#document-fragment') {
                return 1;
            }
            $parent = $parent->parentNode;
        }
    }
    return undef;
};

my $cb_compact = sub {
    my $node = shift;
    # format <author|editor> as compact when it is child of <titleStmt>
    if ( $node->nodeName =~ /^(?:author|editor)$/
          and $node->parentNode->nodeName eq 'titleStmt' )
    {
        return 1;
    }
    # format <publisher|date> as compact when it is child of <publicationStmt>
    if ( $node->nodeName =~ /^(?:publisher|date)$/
          and $node->parentNode->nodeName eq 'publicationStmt' )
    {
        return 1;
    }
    # format <idno> as compact when it in the <idno> container
    if ( $node->nodeName eq 'idno' and $node->parentNode->nodeName eq 'idno' ) {
        return 1;
    }
    return undef;
};

my $in = do { local $/; <> };
my $document = XML::LibXML->load_xml(string => $in);
my $pp = XML::LibXML::PrettyPrint->new(
    indent_string => '    ', # 4 spaces as indentation level
    element => {
        inline   => [ @{$element_header->{inline}}, @{$element_text->{inline}}, $cb_inline ],
        block    => [ @{$element_header->{block}}, @{$element_text->{block}}, $cb_block ],
        compact  => [ @{$element_header->{compact}}, @{$element_text->{compact}}, $cb_compact ],
        preserves_whitespace => [ @{$element_header->{preserves_whitespace}}, @{$element_text->{preserves_whitespace}} ],
    }
);
$pp->pretty_print($document);
my $out = $document->toString;
$out =~ s{\p{Zs}+(<lb\b[^/]*/>)}{$1}g; # dbu: use \p{Zs} instead of \s which seems not to be Unicode safe
print $out;
